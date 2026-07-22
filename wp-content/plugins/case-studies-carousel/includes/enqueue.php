<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {

    if ( ! is_singular() ) {
        return;
    }

    global $post;
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $has_case_study_shortcode = has_shortcode(
        $post->post_content,
        'render_case_study_carousel'
    );
    $related_case_study_carousel = has_shortcode(
        $post->post_content,
        'related_case_study_carousel'
    );

    if ( ! $has_case_study_shortcode && ! $related_case_study_carousel && ! is_singular( 'page' ) ) {
        return;
    }

    wp_enqueue_style(
        'case-study-swiper',
        CASE_STUDY_CAROUSEL_PLUGIN_URL . 'assets/css/swiper-bundle.min.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'case-study-swiper',
        CASE_STUDY_CAROUSEL_PLUGIN_URL . 'assets/js/swiper-bundle.min.js',
        [],
        '1.0.0',
        true
    );

    wp_enqueue_style(
        'case-study-carousel-css',
        CASE_STUDY_CAROUSEL_PLUGIN_URL . 'assets/css/case-study_carousel.css',
        [ 'case-study-swiper' ],
        '1.0.0'
    );

    wp_enqueue_script(
        'case-study-carousel-js',
        CASE_STUDY_CAROUSEL_PLUGIN_URL . 'assets/js/case-study_carousel.js',
        [ 'case-study-swiper' ],
        '1.0.0',
        true
    );
});
