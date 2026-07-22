<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -------------------------------------------------------
 * ACF FILTER
 * Exclude current post from Related Services field
 * -------------------------------------------------------
 */
add_filter(
    'acf/fields/relationship/query/name=related_services',
    function ( $args, $field, $post_id ) {

        if ( is_numeric( $post_id ) ) {
            $args['post__not_in'] = [ (int) $post_id ];
        }

        return $args;
    },
    10,
    3
);

/**
 * Get Related Services with fallback
 *
 * Logic:
 * 1. Use selected services (ACF relationship)
 * 2. Remove current post
 * 3. Limit to 4
 * 4. If not enough → fill with latest services
 *
 * @param int $post_id
 * @param int $limit
 * @return WP_Post[]
 */
function sp_get_related_services( $post_id, $limit = 4 ) {

    // SAFETY
    if ( ! $post_id || ! is_numeric( $post_id ) ) {
        
        return [];
    }

    $group   = get_field( 'related_service_block', $post_id );
    $related = $group['related_services'] ?? [];

    $selected_posts  = [];
    $selected_ids    = [];

    /**
     * 1. Collect selected services
     */
    if ( ! empty( $related ) ) {
        foreach ( (array) $related as $post ) {
            if (
                $post instanceof WP_Post &&
                $post->post_status === 'publish' &&
                $post->ID !== $post_id
            ) {
                $selected_posts[] = $post;
                $selected_ids[]   = $post->ID;
            }
        }
    }

 
    // Trim selected to limit
    $selected_posts = array_slice( $selected_posts, 0, $limit );

    // If enough selected → return
    if ( count( $selected_posts ) === $limit ) {
        return $selected_posts;
    }

    /**
     * 2. Fill remaining with latest services
     */
    $needed = $limit - count( $selected_posts );

    $fallback = get_posts([
        'post_type'      => 'service',
        'posts_per_page' => $needed,
        'post_status'    => 'publish',
        'post__not_in'   => array_merge( [ $post_id ], $selected_ids ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

 
    return array_merge( $selected_posts, $fallback );
}

/**
 * Get max 4 service types per service
 *
 * Priority:
 * 1. Selected service types (if ACF exists)
 * 2. Fill remaining with other terms
 *
 * @param int $service_id
 * @param int $limit
 * @return WP_Term[]
 */
function sp_get_service_types( $service_id, $limit = 4 ) {

    $terms = get_the_terms( $service_id, 'service-type' );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
         return [];
    }

    $selected_term_ids = get_field( 'selected_service_types', $service_id ) ?? [];

    $selected  = [];
    $remaining = [];

    foreach ( $terms as $term ) {
        if ( in_array( $term->term_id, (array) $selected_term_ids, true ) ) {
            $selected[] = $term;
        } else {
            $remaining[] = $term;
        }
    }

    $final = array_merge(
        $selected,
        array_slice( $remaining, 0, max( 0, $limit - count( $selected ) ) )
    );

  

    return $final;
}
