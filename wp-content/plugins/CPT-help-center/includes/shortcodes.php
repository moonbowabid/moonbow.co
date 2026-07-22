<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Help Center Shortcode
 * Usage: [help_center]
 */
add_shortcode( 'help_center', function () {

    // Safety: Elementor + shortcode needs returned output
    ob_start();


    // Template path
    $template_path = HELP_CENTER_PLUGIN_PATH . 'templates/help-center.php';

    if ( file_exists( $template_path ) ) {
        include $template_path;
    } else {
        echo '<p>Help Center template file not found.</p>';
    }

    return ob_get_clean();
});
