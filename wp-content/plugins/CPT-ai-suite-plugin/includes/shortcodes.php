<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode 1 – Grid Layout
 * [ai-suite_footer-listing]
 */
add_shortcode('ai-suite_footer-listing', function () {

    ob_start();
    include AI_SUITE_PLUGIN_PATH . 'templates/shortcodes/ai-suite-grid.php';
    return ob_get_clean();

});

