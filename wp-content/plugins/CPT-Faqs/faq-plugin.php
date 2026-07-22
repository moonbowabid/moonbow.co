<?php
/**
 * Plugin Name: FAQ Block Rendering
 * Description: Handles FAQ rendering.
 * Version: 1.0.0
 * Author: Ewall Developer Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FAQ_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FAQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Includes
 */
require_once FAQ_PLUGIN_PATH . 'includes/enqueue.php';
require_once FAQ_PLUGIN_PATH . 'includes/shortcodes.php';
require_once FAQ_PLUGIN_PATH . 'includes/helpers.php'; 



