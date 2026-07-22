<?php
/**
 * Plugin Name: Case Studies
 * Description: Handles Case Study templates and listings.
 * Version: 1.0.0
 * Author: Ewall Developer Team
 * Stable tag: 1.0.0
 *License: GPL v2 or later
 *License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Includes
 */
require_once CS_PLUGIN_PATH . 'includes/enqueue.php';
require_once CS_PLUGIN_PATH . 'includes/shortcodes.php';
require_once CS_PLUGIN_PATH . 'includes/acf-filters.php';
require_once CS_PLUGIN_PATH . 'includes/helpers.php';

/**
 * Load custom single template for case_study
 */
add_filter( 'single_template', function ( $template ) {
    if ( is_singular( 'case_study' ) ) {
        $custom = CS_PLUGIN_PATH . 'templates/single-case_study.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
});
