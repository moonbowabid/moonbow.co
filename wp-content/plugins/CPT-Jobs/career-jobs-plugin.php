<?php
/**
 * Plugin Name: Career Jobs
 * Description: Custom Post Type for Jobs
 * Version: 1.0.0
 * Author: Ewall Developer Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CPT_JOBS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CPT_JOBS_URL', plugin_dir_url( __FILE__ ) );

require_once CPT_JOBS_PATH . 'includes/shortcodes.php';
require_once CPT_JOBS_PATH . 'includes/enqueue.php';
