<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get related case studies with fallback
 *
 * @param int   $post_id Current post ID
 * @param int   $limit   Number of posts to return
 * @return WP_Post[]
 */
function cs_get_related_case_studies( $post_id, $limit = 2 ) {

    $group   = get_field( 'related_case_studies_group', $post_id );
    $related = $group['related_case_studies'] ?? [];
    $related_description = $group['description'] ?? ''; 
    $case_studies_title=$group['case_studies_title'] ?? 'fill';
    $related_ids = [];
    

    if ( ! empty( $related ) ) {
        foreach ( (array) $related as $post ) {
            if ( $post instanceof WP_Post && $post->post_status === 'publish' ) {
                $related_ids[] = $post->ID;
            }
        }
    }

    // Remove current post
    $related_ids = array_diff( $related_ids, [ $post_id ] );

    // Trim to limit
    $related_ids = array_slice( $related_ids, 0, $limit );

    // If enough selected → return
    if ( count( $related_ids ) === $limit ) {
        return [
            'posts' => get_posts([
                'post_type' => 'case_study',
                'post__in'  => $related_ids,
                'orderby'   => 'post__in',
            ]),
            'description' => $related_description,
            'case_studies_title'       => $case_studies_title, 
        ];
    }

    // Fill remaining slots
    $needed = $limit - count( $related_ids );

    $fallback = get_posts([
        'post_type'      => 'case_study',
        'posts_per_page' => $needed,
        'post__not_in'   => array_merge( [ $post_id ], $related_ids ),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $final_ids = array_merge(
        $related_ids,
        wp_list_pluck( $fallback, 'ID' )
    );

    return [
        'posts' => get_posts([
            'post_type' => 'case_study',
            'post__in'  => $final_ids,
            'orderby'   => 'post__in',

        ]),
        
        'description' => $related_description,
        'case_studies_title'       => $case_studies_title,
    ];
}

/**
 * Ensure only one case_study has the "featured" tag
 */
add_action('save_post_case_study', function ($post_id) {

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    if (!has_term('featured', 'post_tag', $post_id)) {
        return;
    }

    $others = get_posts([
        'post_type'      => 'case_study',
        'posts_per_page' => -1,
        'post__not_in'   => [$post_id],
        'tax_query'      => [
            [
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => 'featured',
            ],
        ],
        'fields' => 'ids',
    ]);

    foreach ($others as $other_id) {
        wp_remove_object_terms($other_id, 'featured', 'post_tag');
    }
});

 
/**
 * When featured case_study is trashed,
 * make the latest published case_study featured
 */
add_action('wp_trash_post', function ($post_id) {

    if (get_post_type($post_id) !== 'case_study') {
        return;
    }

    if (!has_term('featured', 'post_tag', $post_id)) {
        return;
    }

    // Remove featured from trashed post
    wp_remove_object_terms($post_id, 'featured', 'post_tag');

    // Get latest published case study
    $latest = get_posts([
        'post_type'      => 'case_study',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'post__not_in'   => [$post_id],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if (!empty($latest)) {
        wp_set_object_terms($latest[0]->ID, 'featured', 'post_tag', false);
    }
});

/**
 * When featured case_study is deleted,
 * make the latest published case_study featured
 */
add_action('before_delete_post', function ($post_id) {

    if (get_post_type($post_id) !== 'case_study') {
        return;
    }

    if (!has_term('featured', 'post_tag', $post_id)) {
        return;
    }

    // Get latest published case study BEFORE delete
    $latest = get_posts([
        'post_type'      => 'case_study',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'post__not_in'   => [$post_id],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    // Assign featured to latest
    if (!empty($latest)) {
        wp_set_object_terms($latest[0]->ID, 'featured', 'post_tag', false);
    }
});
