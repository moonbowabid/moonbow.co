<?php
/**
 * Scheduling subsystem bootstrap.
 *
 * Wires the REST controller. Tasks are NOT shipped from this package —
 * consumers (the MCP plugin, future plugins, or mu-plugin code) register
 * their own tasks via wp_sd_register_task() from their own bootstrap code.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap. Idempotent.
 */
class Scheduling_Bootstrap {

	/**
	 * Initialise the scheduling subsystem.
	 *
	 * Safe to call multiple times — second and later calls are no-ops.
	 */
	public static function init(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;

		Scheduled_Tasks_REST::init();
		Failed_Action_Cleaner::init();
	}
}
