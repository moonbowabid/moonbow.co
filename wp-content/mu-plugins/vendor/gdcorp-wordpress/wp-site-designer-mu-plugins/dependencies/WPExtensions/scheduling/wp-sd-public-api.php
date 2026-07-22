<?php
/**
 * Public global API for the scheduling subsystem.
 *
 * Cross-plugin consumers should use these functions rather than referencing the
 * (Mozart-prefixed) Scheduled_Tasks namespace. The function names live in the
 * global namespace and are stable across Mozart packaging.
 *
 * Loaded via Composer's `autoload.files` so the symbols are always present in
 * any consumer of this package.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling\Scheduled_Tasks;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'wp_sd_register_task' ) ) {
	/**
	 * Register a task handler.
	 *
	 * @param string             $name    Task name (used in hook `wp_sd_task_{$name}`).
	 * @param callable           $handler Receives the assoc payload (with reserved `_`-prefix keys stripped).
	 * @param array<int, string> $caps    Capabilities required at run time.
	 * @param array              $meta    Optional metadata: label, description, payload_schema.
	 */
	function wp_sd_register_task( string $name, callable $handler, array $caps = array(), array $meta = array() ): void {
		Scheduled_Tasks::instance()->register_task( $name, $handler, $caps, $meta );
	}
}

if ( ! function_exists( 'wp_sd_schedule' ) ) {
	/**
	 * Schedule a registered task.
	 *
	 * @param string   $name      Registered task name.
	 * @param array    $payload   Assoc payload passed to the handler.
	 * @param int      $timestamp Unix timestamp (UTC).
	 * @param int|null $interval  Recurrence interval in seconds. Null = single-shot.
	 * @return int|null Action Scheduler action ID, or null on failure.
	 */
	function wp_sd_schedule( string $name, array $payload, int $timestamp, ?int $interval = null ): ?int {
		return Scheduled_Tasks::instance()->schedule( $name, $payload, $timestamp, $interval );
	}
}

if ( ! function_exists( 'wp_sd_cancel' ) ) {
	/**
	 * Cancel a pending action by ID.
	 *
	 * @param int $action_id Action Scheduler action ID.
	 */
	function wp_sd_cancel( int $action_id ): bool {
		return Scheduled_Tasks::instance()->cancel( $action_id );
	}
}

if ( ! function_exists( 'wp_sd_list_pending' ) ) {
	/**
	 * List pending actions in the wp-site-designer group.
	 *
	 * @param string|null $name     Optional task-name filter.
	 * @param int         $per_page Page size (1-200). Default 100.
	 * @param int         $page     1-based page number. Default 1.
	 * @return array<int, array{id:int, hook:string, scheduled_at:string, payload:array}>
	 */
	function wp_sd_list_pending( ?string $name = null, int $per_page = 100, int $page = 1 ): array {
		return Scheduled_Tasks::instance()->list_pending( $name, $per_page, $page );
	}
}

if ( ! function_exists( 'wp_sd_count_pending' ) ) {
	/**
	 * Count pending actions in the wp-site-designer group.
	 *
	 * @param string|null $name Optional task-name filter.
	 */
	function wp_sd_count_pending( ?string $name = null ): int {
		return Scheduled_Tasks::instance()->count_pending( $name );
	}
}

if ( ! function_exists( 'wp_sd_get_catalog' ) ) {
	/**
	 * Get the catalog of registered tasks (no handler callables).
	 *
	 * @return array<int, array{name:string, label:string, description:string, payload_schema:array, caps:array<int,string>}>
	 */
	function wp_sd_get_catalog(): array {
		return Scheduled_Tasks::instance()->get_catalog();
	}
}

if ( ! function_exists( 'wp_sd_has_task' ) ) {
	/**
	 * Whether a task is registered.
	 *
	 * @param string $name Task name.
	 */
	function wp_sd_has_task( string $name ): bool {
		return Scheduled_Tasks::instance()->has_task( $name );
	}
}
