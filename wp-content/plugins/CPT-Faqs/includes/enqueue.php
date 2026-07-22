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
        has_shortcode( $post->post_content, 'render_faq' );

    if ( is_singular( 'faq' ) || $has_case_study_shortcodes ) {

        wp_enqueue_style(
            'faq-render-css',
            FAQ_PLUGIN_URL . 'assets/css/faqs-render.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'faq-render-js',
            FAQ_PLUGIN_URL . 'assets/js/faq-render.js',
            [ 'jquery' ],
            '1.0',
            true
        );
    }
});

