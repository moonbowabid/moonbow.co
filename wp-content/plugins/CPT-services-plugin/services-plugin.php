<?php
/**
 * Plugin Name: Services Plugin
 * Description: Handles Services templates and listings.
 * Version: 1.0.0
 * Author: Ewall Developer Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SERVICE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SERVICE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Includes
 */
require_once SERVICE_PLUGIN_PATH . 'includes/enqueue.php';
require_once SERVICE_PLUGIN_PATH . 'includes/shortcodes.php';
require_once SERVICE_PLUGIN_PATH . 'includes/helpers.php';

/**
 * Load custom single template for case_study
 */
add_filter( 'single_template', function ( $template ) {
    if ( is_singular( 'service' ) ) {
        $custom = SERVICE_PLUGIN_PATH . 'templates/single-service.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
});
add_filter('manage_service_posts_columns', function($columns){
    $columns['menu_order'] = 'Order';
    return $columns;
});

add_action('manage_service_posts_custom_column', function($column, $post_id){
    if ($column == 'menu_order') {
        echo get_post_field('menu_order', $post_id);
    }
}, 10, 2);