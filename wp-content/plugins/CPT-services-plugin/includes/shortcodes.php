<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode 1 – Grid Layout
 * [services_grid]
 */
add_shortcode('services_grid', function () {

    ob_start();
    include SERVICE_PLUGIN_PATH . 'templates/shortcodes/services-grid.php';
    return ob_get_clean();

});

