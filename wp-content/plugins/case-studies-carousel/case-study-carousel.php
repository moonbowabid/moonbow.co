<?php
/**
 * Plugin Name: Case study carousel
 * Description: Handles Case study carousel on what we do(subpages).
 * Version: 1.0.0
 * Author: Ewall Developer Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CASE_STUDY_CAROUSEL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CASE_STUDY_CAROUSEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Includes
 */
require_once CASE_STUDY_CAROUSEL_PLUGIN_PATH . 'includes/enqueue.php';
require_once CASE_STUDY_CAROUSEL_PLUGIN_PATH . 'templates/shortcode.php';
require_once CASE_STUDY_CAROUSEL_PLUGIN_PATH . 'includes/helpers.php';


