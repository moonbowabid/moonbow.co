<?php
/**
 * Plugin Name: WordPress Site Designer MU-Plugins
 * Description: MU-Plugins for WordPress Site Designer integration
 * Version: 2.6.0
 * Author: GoDaddy
 * Requires PHP: 7.4
 *
 * @package wp-site-designer-mu-plugins
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner;

use GoDaddy\WordPress\Plugins\SiteDesigner\Api\Media_Sideload_Api;
use GoDaddy\WordPress\Plugins\SiteDesigner\Api\Site_Designer_Api;
use GoDaddy\WordPress\Plugins\SiteDesigner\Auth\JWT_Auth;
use GoDaddy\WordPress\Plugins\SiteDesigner\Auth\Signature_Auth;
use GoDaddy\WordPress\Plugins\SiteDesigner\Auth\WP_Public_Api_Client;
use GoDaddy\WordPress\Plugins\SiteDesigner\Compat\Compatibility_Modal;
use GoDaddy\WordPress\Plugins\SiteDesigner\Compat\Compatibility_Notices;
use GoDaddy\WordPress\Plugins\SiteDesigner\Compat\Plugin_Compatibility;
use GoDaddy\WordPress\Plugins\SiteDesigner\Compat\Theme_Compatibility;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Compatibility_Bridge;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\FullStory_Iframe_Tracker;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Iframe_Support;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Hide_Admin_Bar;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Gutenberg_Support;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Cookie_Status_Bridge;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Navigation_Bridge;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Change_Highlighter;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\GoDaddy_Media_Tab;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Media_Upload;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Safari_Storage_Access;
use GoDaddy\WordPress\Plugins\SiteDesigner\Extensions\Viewport_Bridge;
use GoDaddy\WordPress\Plugins\SiteDesigner\Utils\Config;
use GoDaddy\WordPress\Plugins\SiteDesigner\Utils\Request_Validator;
use GoDaddy\WordPress\Plugins\SiteDesigner\Utils\Iframe_Context_Detector;
use GoDaddy\WordPress\Plugins\SiteDesigner\Woo;

// WP extensions (Mozart-prefixed from gdcorp-wordpress/site-designer-wp-extensions).
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Block_Markup_Annotator;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Bootstrap as WPExtensionsBootstrap;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Draft_Mode;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Editor_Welcome_Guide;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Font_Pairing;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\FullStory_Tracker;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Global_Styles_Sync;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Logo_Settings;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Native_UI_Loader;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\OAuth_Complete;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Palette_Switcher;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Revision_Restore;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling\Scheduling_Bootstrap;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Style_Kit;
use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Theme_Reset;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load gdmu_site_designer_should_run() scoping functions.
require_once __DIR__ . '/should_run.php';

// Load production autoloader (Mozart-prefixed dependencies + plugin code).
// This is a Composer-generated autoloader located in dependencies/.
$gdmu_site_designer_dependencies_autoloader = __DIR__ . '/dependencies/autoload.php';
if ( file_exists( $gdmu_site_designer_dependencies_autoloader ) ) {
	require $gdmu_site_designer_dependencies_autoloader;
}

// Mozart 0.7 does not honor `autoload.files` entries from packaged dependencies, so the
// wp-extensions scheduling public API (global functions wp_sd_*) is loaded explicitly.
$gdmu_site_designer_wpext_public_api = __DIR__ . '/dependencies/WPExtensions/scheduling/wp-sd-public-api.php';
if ( file_exists( $gdmu_site_designer_wpext_public_api ) ) {
	require_once $gdmu_site_designer_wpext_public_api;
}

// Action Scheduler — Composer-required, excluded from Mozart prefixing.
//
// Loaded lazily: only if (a) something else (WC, etc.) already loaded AS this
// request, or (b) we've previously activated scheduling on this site (option
// flag set on first successful schedule). Otherwise wp-extensions calls the
// `wp_sd_ensure_action_scheduler` action below to load on demand.
//
// `class_exists( 'ActionScheduler' )` covers the WC-already-loaded case so we
// don't double-include AS; the version registry would dedupe anyway, but we
// avoid the redundant file work.
$gdmu_site_designer_as_bootstrap = __DIR__ . '/dependencies/action-scheduler/action-scheduler.php';
$gdmu_site_designer_load_as      = static function () use ( $gdmu_site_designer_as_bootstrap ) {
	if ( \class_exists( 'ActionScheduler' ) ) {
		return;
	}
	if ( ! \file_exists( $gdmu_site_designer_as_bootstrap ) ) {
		return;
	}
	require_once $gdmu_site_designer_as_bootstrap;

	// `plugins_loaded` already fired (mid-request schedule) → AS won't auto-init; kick the registry.
	if ( \did_action( 'plugins_loaded' ) && \class_exists( 'ActionScheduler_Versions' ) ) {
		\ActionScheduler_Versions::initialize_latest_version();
	}
};

if (
	\class_exists( 'ActionScheduler' )
	|| \function_exists( 'as_schedule_single_action' )
	|| (bool) \get_option( 'wp_sd_scheduling_activated', false )
) {
	$gdmu_site_designer_load_as();
}

// First-schedule on-demand loader. wp-extensions fires this from Scheduled_Tasks::schedule().
\add_action( 'wp_sd_ensure_action_scheduler', $gdmu_site_designer_load_as );

// Load dev dependencies autoloader (only present in development environment).
// This includes PHPUnit, Mockery, code standards, etc.
$gdmu_site_designer_vendor_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $gdmu_site_designer_vendor_autoloader ) ) {
	require_once $gdmu_site_designer_vendor_autoloader;
}

define( 'GDMU_SITE_DESIGNER_VERSION', '2.6.0' );
define( 'GDMU_SITE_DESIGNER_PATH', __DIR__ );
define( 'GDMU_SITE_DESIGNER_URL', plugins_url( '', __FILE__ ) );
define( 'GDMU_SITE_DESIGNER_PRESENT_OPTION', 'gdmu_site_designer' );

/**
 * Load plugin textdomains and wp-extensions package infrastructure.
 *
 * The mu-plugin textdomain (wp-site-designer-mu-plugins) loads from:
 * 1. WP_LANG_DIR/plugins/wp-site-designer-mu-plugins-{locale}.mo (global translations)
 * 2. Plugin's languages directory (bundled translations)
 *
 * WPExtensionsBootstrap::init() loads the wp-extensions textdomain from the
 * Mozart-prefixed location: dependencies/WPExtensions/languages/.
 * Files are placed there by post-install.php during composer install/update.
 */
add_action(
	'muplugins_loaded',
	function () {
		// Calculate path relative to WPMU_PLUGIN_DIR.
		$rel_path = str_replace( trailingslashit( WPMU_PLUGIN_DIR ), '', __DIR__ );
		load_muplugin_textdomain( 'wp-site-designer-mu-plugins', $rel_path . '/languages' );

		if ( class_exists( WPExtensionsBootstrap::class ) ) {
			WPExtensionsBootstrap::init();
		}

		// Scheduling subsystem: registers task handlers + REST routes. Must run early
		// (and unconditionally of native_ui_enabled) so AS-fired hooks are registered
		// when the queue runs and the REST endpoints are available to other plugins.
		if ( class_exists( Scheduling_Bootstrap::class ) ) {
			Scheduling_Bootstrap::init();
		}
	}
);

$gdmu_site_designer_config = new Config();
$gdmu_site_designer_config->load_from_json( GDMU_SITE_DESIGNER_PATH . '/config/site-designer.json' );

// Compatibility system runs in wp-admin whenever Site Designer is activated,
// regardless of iframe context or native_ui_enabled flag.
add_action(
	'plugins_loaded',
	function () {
		if ( Iframe_Context_Detector::is_plugin_activated() && is_admin() ) {
			Plugin_Compatibility::init();
			Theme_Compatibility::init();
			Compatibility_Notices::init();
			Compatibility_Modal::init();
		}
	},
	0
);

// WP extensions (native UI + design tools) run independently of iframe integration.
// Gated only on native_ui_enabled flag; must not load inside iframe context.
// The flag check MUST run on plugins_loaded (not at MU-plugin load time): this file loads
// before regular plugins, so $GLOBALS['wpaas_feature_flag'] may not exist yet when the
// MU-plugin file is first parsed. Priority 20 runs after typical platform bootstrap hooks.
add_action(
	'plugins_loaded',
	function () use ( $gdmu_site_designer_config ) {
		if ( ! gdmu_site_designer_is_native_ui_enabled() ) {
			return;
		}

		// Early exit if this is an iframe request.
		if ( gdmu_site_designer_is_iframe_request() ) {
			return;
		}

		if ( is_admin() ) {
			FullStory_Tracker::init();
			Editor_Welcome_Guide::init();
		}

		Native_UI_Loader::init( $gdmu_site_designer_config );
		Font_Pairing::init();
		Palette_Switcher::init();
		Revision_Restore::init();
		Style_Kit::init();
		Theme_Reset::init();
		Global_Styles_Sync::init();
		Logo_Settings::init();
		Block_Markup_Annotator::init();
		OAuth_Complete::init( $gdmu_site_designer_config );
		Draft_Mode::init();
	},
	20
);

// Early exit if core iframe integration should not run in current context.
if ( ! gdmu_site_designer_should_run() ) {
	return;
}

$gdmu_site_designer_request_validator = new Request_Validator( $gdmu_site_designer_config );
$gdmu_site_designer_request_validator->parse();

// Authentication providers.
$gdmu_site_designer_jwt_auth       = new JWT_Auth( $gdmu_site_designer_config );
$gdmu_site_designer_wp_public_api  = new WP_Public_Api_Client( $gdmu_site_designer_config );
$gdmu_site_designer_signature_auth = new Signature_Auth( $gdmu_site_designer_wp_public_api );

$gdmu_site_designer_api = new Site_Designer_Api(
	$gdmu_site_designer_request_validator,
	$gdmu_site_designer_jwt_auth,
	$gdmu_site_designer_signature_auth
);
$gdmu_site_designer_api->register_endpoints();

$gdmu_site_designer_media_sideload_api = new Media_Sideload_Api(
	$gdmu_site_designer_config,
	$gdmu_site_designer_request_validator
);
$gdmu_site_designer_media_sideload_api->register_endpoints();

add_action(
	'plugins_loaded',
	function () use ( $gdmu_site_designer_request_validator ) {
		// Check if this is a valid iframe request.
		$is_iframe_context = Iframe_Context_Detector::is_valid_request( $gdmu_site_designer_request_validator );
		$is_plugin_active  = Iframe_Context_Detector::is_plugin_activated();
		$is_woo_active     = function_exists( 'WC' );

		// Core functionalities that should run for valid iframe requests.
		if ( $is_iframe_context ) {
			// Enables WordPress to work properly within an iframe (security headers, cookie handling).
			Iframe_Support::init();
			// Hides the WordPress admin bar in iframe context for cleaner UI.
			Hide_Admin_Bar::init();
			// Bridges navigation state changes between iframe and parent window.
			Navigation_Bridge::init();
			// Bridges cookie status and authentication between iframe and parent window.
			Cookie_Status_Bridge::init();
			// Adds Gutenberg customizations for iframe context (content saver, welcome message, status bar).
			Gutenberg_Support::init();
			// Bridges viewport/window size data between iframe and parent window.
			Viewport_Bridge::init();
			// Bridges compatibility status (incompatible plugins/themes) to Site Designer parent window.
			Compatibility_Bridge::init();
			// Enables FullStory session tracker within iframe context.
			FullStory_Iframe_Tracker::init();
			// Handles media upload functionality within iframe context.
			Media_Upload::init();
			// Change highlighter.
			Change_Highlighter::init();
			// GoDaddy Media Library tab for browsing and importing assets via the parent MFE.
			GoDaddy_Media_Tab::init();
		}

		if ( $is_plugin_active ) {
			Safari_Storage_Access::init();
		}

		if ( $is_woo_active ) {
			// Automatically configures WooCommerce and skips setup wizard.
			Woo\Setup::init();
		}
	},
	0
);

Woo\Setup::setup();
