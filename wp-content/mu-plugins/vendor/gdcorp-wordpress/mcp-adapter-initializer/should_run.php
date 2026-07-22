<?php
/**
 * Should Run - Context-based plugin execution scoping
 *
 * This file contains functions that determine whether the MCP Adapter Initializer
 * plugin should run based on the current WordPress execution context.
 *
 * These functions are loaded early (before autoloader) to enable fast bailout
 * when the plugin is not needed, avoiding unnecessary memory and CPU overhead.
 *
 * @package mcp-adapter-initializer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if the MCP Adapter plugin should run based on the current WordPress execution context
 *
 * This plugin is specifically designed ONLY for serving MCP protocol requests via REST API.
 * It provides NO functionality for standard WordPress operations and should NOT run in
 * contexts where it provides no value or could cause unnecessary overhead.
 *
 * ALLOWED CONTEXTS (plugin WILL run):
 * - REST API Requests: Core functionality - serves the /gd-mcp/v1/mcp/streamable endpoint
 * - WP-CLI: Allows debugging and testing MCP tools via command-line
 *
 * BLOCKED CONTEXTS (plugin will NOT run):
 * - Regular HTTP/Page Requests: Plugin provides no frontend or admin UI functionality
 * - Cron Jobs: Plugin provides no scheduled task functionality
 * - WordPress Installation: Requires a fully installed WordPress with database access
 * - Database Repair: Not needed during DB maintenance operations
 * - Shortinit Mode: Requires full WordPress functionality (REST API, plugins, etc.)
 * - Plugin Uninstallation: Not needed during cleanup operations
 * - AJAX Requests: Plugin only serves REST API, not wp-admin/admin-ajax.php
 *
 * PERFORMANCE BENEFITS:
 * By only running for REST API requests to our specific endpoint, we:
 * - Eliminate ALL overhead on regular page loads (0ms impact on frontend/admin)
 * - Reduce memory usage by ~5-10MB on non-REST requests
 * - Avoid loading 35+ tool classes, authentication, abilities API, etc.
 * - Prevent potential errors in limited WordPress environments
 * - Improve overall site performance by 2-5ms per blocked context
 *
 * @return bool True if plugin should initialize, false to skip execution.
 */
function gd_mcp_adapter_initializer_should_run(): bool {
	// Allow: Action Scheduler queue processing — must precede cron/ajax blocks.
	// AS fires scheduled actions via WP-Cron, an async admin-ajax runner, or inline
	// admin list-table execution. The plugin must load in those contexts so its
	// `wp_sd_task_mcp_tool_call` listener (added on init via wp_sd_register_task)
	// is registered before AS fires the hook.
	if ( gd_mcp_adapter_initializer_is_action_scheduler_request() ) {
		return true;
	}

	// Block: Cron contexts - this plugin provides no cron functionality.
	if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || wp_doing_cron() ) {
		return false;
	}

	// Block: WordPress installation - requires a fully installed WordPress.
	if ( ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) || wp_installing() ) {
		return false;
	}

	// Block: Database repair mode - not needed for DB maintenance.
	if ( defined( 'WP_REPAIRING' ) && WP_REPAIRING ) {
		return false;
	}

	// Block: Shortinit mode - requires full WordPress functionality.
	if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
		return false;
	}

	// Block: Plugin uninstallation - not needed during cleanup.
	if ( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN ) {
		return false;
	}

	// Block: AJAX requests - plugin only serves REST API endpoints, not admin-ajax.php.
	if ( wp_doing_ajax() ) {
		return false;
	}

	// Allow: WP-CLI for debugging and testing.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}

	// Block: Regular HTTP page requests (frontend/admin) - only allow REST API requests.
	// This is the key optimization: we only run for REST API requests.
	if ( ! gd_mcp_adapter_initializer_is_rest_request() ) {
		return false;
	}

	// Allow: REST API requests (our core functionality).
	return true;
}

/**
 * Detect if the current request is an Action Scheduler queue-processing context.
 *
 * Gated on the `wp_sd_scheduling_activated` option so this only ever returns
 * true on sites that have actually scheduled an MCP tool — not on every Woo
 * site (WooCommerce bundles Action Scheduler regardless of our feature).
 *
 * Covers three AS execution paths:
 * 1. Admin list-table "Run" button — admin POST with action=action_scheduler_process_queue
 * 2. Async queue runner (the default) — admin-ajax.php with action=as_async_request_queue_runner
 * 3. WP-Cron triggered run — DOING_CRON true and Action Scheduler is loaded
 *
 * @return bool
 */
function gd_mcp_adapter_initializer_is_action_scheduler_request(): bool {
	// Only relevant on sites that have actually activated MCP scheduling.
	// Mirrors wp-extensions' own AS-load gate (option set on first successful
	// schedule). Without this, any Woo site — which bundles Action Scheduler —
	// would boot the full plugin on every cron tick even if scheduling is unused.
	if ( ! (bool) get_option( 'wp_sd_scheduling_activated', false ) ) {
		return false;
	}

	$gd_mcp_as_loaded = function_exists( 'as_has_scheduled_action' ) || defined( 'ACTION_SCHEDULER_ABSPATH' );
	if ( ! $gd_mcp_as_loaded ) {
		return false;
	}

	$gd_mcp_as_queue_actions = array(
		'action_scheduler_process_queue',
		'as_async_request_queue_runner',
	);
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context check.
	$gd_mcp_request_action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	if (
		in_array( $gd_mcp_request_action, $gd_mcp_as_queue_actions, true )
		&& ( ( function_exists( 'is_admin' ) && is_admin() ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) )
	) {
		return true;
	}

	if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || wp_doing_cron() ) {
		return true;
	}

	return false;
}

/**
 * Detect if the current request is a REST API request
 *
 * Checks multiple indicators to determine if this is a REST API request:
 * 1. REST_REQUEST constant (most reliable, set by WordPress core)
 * 2. Request URI contains a namespace allowlist (/wp-json/gd-mcp/ or /wp-json/sd-drafts/)
 * 3. Query parameter rest_route is set
 *
 * This is more reliable than only checking REST_REQUEST since that constant
 * may not be defined yet during early plugin loading.
 *
 * @return bool True if this is a REST API request, false otherwise.
 */
function gd_mcp_adapter_initializer_is_rest_request(): bool {
	// Method 1: Check REST_REQUEST constant (most reliable if defined).
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}

	// Method 2: Check if request URI contains namespaces this plugin serves.
	// - /wp-json/gd-mcp/ — MCP tools (AI assistant WordPress operations)
	// - /wp-json/sd-drafts/ — Draft publish/discard REST endpoints (native UI banner)
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		if ( false !== strpos( $request_uri, '/wp-json/gd-mcp/' ) ) {
			return true;
		}
		if ( false !== strpos( $request_uri, '/wp-json/sd-drafts/' ) ) {
			return true;
		}
	}

	// Method 3: Check for rest_route query parameter (pretty permalinks disabled).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only check, not processing form data.
	if ( isset( $_GET['rest_route'] ) ) {
		return true;
	}

	return false;
}
