<?php
/**
 * Plugin Name: AGRF Frontend Dependency Shim
 * Description: Registers the `agrfuxd-frontend` style in contexts where the base plugin fails to
 *              (chiefly the Elementor editor), so `agrfuxd-elementor-widgets` no longer triggers the
 *              "dependencies that are not registered" notice added in WordPress 6.9.1.
 *
 *              Root cause: "Advanced Gallery + Repeater Fields for ACF" only enqueues
 *              `agrfuxd-frontend` on `wp_enqueue_scripts` (frontend), but enqueues the dependent
 *              `agrfuxd-elementor-widgets` on `elementor/editor/after_enqueue_styles` too — where
 *              `wp_enqueue_scripts` never runs, leaving the dependency unregistered.
 *
 *              Safe to remove once the plugin registers `agrfuxd-frontend` in the editor context.
 *              Deploy target: staging only.
 * Version:     1.1.0
 * Author:      Ewall Developer Team
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the `agrfuxd-frontend` style so it can satisfy the `agrfuxd-elementor-widgets`
 * dependency. Idempotent — bows out if the base plugin already registered it.
 */
function agrfuxd_shim_register_frontend_style() {
	if ( wp_style_is( 'agrfuxd-frontend', 'registered' ) ) {
		return;
	}

	// Point at the plugin's real frontend CSS so a rendered widget keeps its styles; fall back to a
	// dependency-only alias (src = false) if the file is missing.
	$rel  = 'advanced-gallery-repeater-fields-for-acf/assets/css/frontend.css';
	$file = WP_PLUGIN_DIR . '/' . $rel;
	$src  = file_exists( $file ) ? plugins_url( $rel ) : false;
	$ver  = defined( 'AGRFUXD_VERSION' ) ? AGRFUXD_VERSION : ( file_exists( $file ) ? (string) filemtime( $file ) : false );

	wp_register_style( 'agrfuxd-frontend', $src, array(), $ver );
}

// Root cause: the Elementor editor enqueues the dependent style but never registers the base one.
// Run just before the plugin's own priority-10 callbacks on these hooks.
add_action( 'elementor/editor/after_enqueue_styles', 'agrfuxd_shim_register_frontend_style', 9 );
add_action( 'elementor/frontend/after_enqueue_styles', 'agrfuxd_shim_register_frontend_style', 9 );

// Frontend safety net (harmless — guarded against double registration).
add_action( 'wp_enqueue_scripts', 'agrfuxd_shim_register_frontend_style', 0 );
