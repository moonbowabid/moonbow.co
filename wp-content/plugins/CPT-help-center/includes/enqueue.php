<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue Help Center assets
 */
add_action( 'wp_enqueue_scripts', function () {

    global $post;

    // Check if current post is a Help Center CPT or contains the [help_center] shortcode
    $has_help_center_shortcode = is_a( $post, 'WP_Post' ) &&
        has_shortcode( $post->post_content, 'help_center' );

    if ( is_singular( 'help-center' ) || $has_help_center_shortcode ) {

        // Enqueue main Help Center CSS
        wp_enqueue_style(
            'help-center-css',
            HELP_CENTER_PLUGIN_URL . 'assets/css/help-center.css',  
            [],
            '1.1'
        );  
         wp_enqueue_style(
            'help-center-signlepage-css',
            HELP_CENTER_PLUGIN_URL . 'assets/css/help-center-singlepage.css',  
            [],
            '1.1'
        );  
        wp_enqueue_script(
            'help-center-js',
            HELP_CENTER_PLUGIN_URL . 'assets/js/help-center.js',
            [],
            '1.1',
            true  
        );   
    }
});
