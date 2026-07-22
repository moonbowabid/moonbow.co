<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {

    if ( ! is_singular() ) {
        return;
    }

    global $post;

    if ( ! $post ) {
        return;
    }

    $has_case_study_shortcodes =
        has_shortcode( $post->post_content, 'case_studies_grid' ) ||
        has_shortcode( $post->post_content, 'related_case_studies' );

    if ( is_singular( 'case_study' ) || $has_case_study_shortcodes ) {

        wp_enqueue_style(
            'case-studies-css',
            CS_PLUGIN_URL . 'assets/css/case-studies.css',
            [],
            '1.0'
        );

        wp_enqueue_style(
            'case-studies-listing-css',
            CS_PLUGIN_URL . 'assets/css/case-studies-listing.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'case-studies-js',
            CS_PLUGIN_URL . 'assets/js/case-studies.js',
            [ 'jquery' ],
            '1.0',
            true
        );
    }
});

