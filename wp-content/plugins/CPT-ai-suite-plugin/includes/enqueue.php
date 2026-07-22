<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {

    // Always load when shortcode is used
    global $post;

    $has_services_shortcode = is_a( $post, 'WP_Post' ) &&
        has_shortcode( $post->post_content, 'ai-suite_footer-listing' );

    if ( is_singular( 'ai-suite' ) || $has_services_shortcode ) {

        wp_enqueue_style(
            'ai-suite-css',
            AI_SUITE_PLUGIN_URL . 'assets/css/ai-suite.css',
            [],
            '1.0'
        );

        wp_enqueue_style(
            'ai-suite-grid-css',
            AI_SUITE_PLUGIN_URL . 'assets/css/ai-suite-grid.css',
            [],
            '1.0'
        );
    }

});
