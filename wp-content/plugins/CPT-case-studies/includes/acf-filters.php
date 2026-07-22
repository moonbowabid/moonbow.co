<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exclude current post from related case studies field
 */
add_filter(
    'acf/fields/relationship/query/name=related_case_studies',
    function ( $args, $field, $post_id ) {

        if ( is_numeric( $post_id ) ) {
            $args['post__not_in'] = [ $post_id ];
        }

        return $args;
    },
    10,
    3
);
