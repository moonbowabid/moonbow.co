<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -------------------------------------------------------
 * Get Help Categories
 * -------------------------------------------------------
 */
function hc_get_help_categories() {

    return get_terms([
        'taxonomy'   => 'help_category',
        'hide_empty' => true,
    ]);
}

/**
 * -------------------------------------------------------
 * Get Help Posts by Category
 * -------------------------------------------------------
 */
function hc_get_help_posts_by_category( $term_id ) {

    return new WP_Query([
        'post_type'      => 'help',
        'posts_per_page' => -1,
        'tax_query'      => [[
            'taxonomy' => 'help_category',
            'terms'    => $term_id,
        ]],
    ]);
}
