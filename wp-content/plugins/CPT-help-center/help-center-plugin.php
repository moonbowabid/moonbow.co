<?php
    /**
     * Plugin Name: Help center
     * Description: Handles HELP CENTER rendering.
     * Version: 1.0.0
     * Author: Ewall Developer Team
     * License: GPL v2 or later
     * License URI: https://www.gnu.org/licenses/gpl-2.0.html
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    define( 'HELP_CENTER_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    define( 'HELP_CENTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

    /**
     * Includes
     */
    require_once HELP_CENTER_PLUGIN_PATH . 'includes/shortcodes.php'; 
    require_once HELP_CENTER_PLUGIN_PATH . 'includes/enqueue.php'; 
    require_once HELP_CENTER_PLUGIN_PATH . 'includes/helpers.php'; 


    /**
     * Load custom   for case_study
     */
   add_filter( 'single_template', function ( $template ) {

    if ( is_singular( 'help-center' ) ) {

        $plugin_template = plugin_dir_path( __FILE__ ) . 'templates/single-help-center.php';

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
});




