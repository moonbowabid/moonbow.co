<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get FAQs for a page:
 * - Use selected FAQs (ACF relationship)
 * - Fill up to $limit with latest FAQs if needed
 *
 * @param int|null $post_id
 * @param int $limit
 * @return WP_Post[]
 */
if ( ! function_exists( 'faq_get_page_faqs' ) ) {
    function faq_get_page_faqs( $post_id = null ) {

        $post_id = $post_id ?: get_the_ID();
        if ( ! $post_id ) {
            return [];
        }

        // Get selected FAQs from ACF relationship field
        $selected_faqs = get_field( 'render_faq', $post_id );

        if ( ! is_array( $selected_faqs ) || empty( $selected_faqs ) ) {
            return [];
        }

        return $selected_faqs;
    }
}