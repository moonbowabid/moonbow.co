<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {

    // Always load when shortcode is used
    global $post;

    $has_services_shortcode = is_a( $post, 'WP_Post' ) &&
        has_shortcode( $post->post_content, 'services_grid' );

    if ( is_singular( 'service' ) || $has_services_shortcode ) {

        wp_enqueue_style(
            'services-css',
            SERVICE_PLUGIN_URL . 'assets/css/services.css',
            [],
            '1.1'
        );

        wp_enqueue_style(
            'services-grid-css',
            SERVICE_PLUGIN_URL . 'assets/css/services-grid.css',
            [],
            '1.0'
        );
    }

});
