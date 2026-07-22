<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Case Studies Carousel (ACF Repeater)
 */
function case_study_carousel_get_items( $post_id = null ) {

    $post_id = $post_id ?: get_the_ID();

    if ( ! $post_id ) {
        return [];
    }

    $case_studies = get_field( 'case_study_carousel', $post_id );

    if ( empty( $case_studies ) || ! is_array( $case_studies ) ) {
        return [];
    }

    return $case_studies;
}
