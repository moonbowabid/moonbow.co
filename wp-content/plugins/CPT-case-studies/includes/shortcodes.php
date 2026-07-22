<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode 1 – Grid Layout
 * [case_studies_grid]
 */
add_shortcode('case_studies_grid', function () {

    ob_start();
    include CS_PLUGIN_PATH . 'templates/shortcodes/case-studies-grid.php';
    return ob_get_clean();

});


/**
 * Shortcode 2 – Other case studies
 * [related_case_studies]
 */

add_shortcode( 'related_case_studies', function ( $atts ) {

    wp_enqueue_style(
        'related-case-studies-listing-css',
        CS_PLUGIN_URL . 'assets/css/related-case-studies.css',
        [],
        '1.0'
    );

    $atts = shortcode_atts([
        'post_id' => get_the_ID(),
    ], $atts );

    $post_id = (int) $atts['post_id'];

    if ( ! $post_id ) {
        return '';
    }

    $data = cs_get_related_case_studies( $post_id );
 
    if ( empty( $data['posts'] ) ) {
        return '';
    }

    ob_start();

    include CS_PLUGIN_PATH . 'templates/shortcodes/related-case-studies.php';

    return ob_get_clean();
});