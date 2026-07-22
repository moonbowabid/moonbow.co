<?php
/**
 * Periodic cleanup of FAILED actions in our AS group.
 *
 * Action Scheduler's default cleaner only purges COMPLETE and CANCELED actions
 * (30-day retention). FAILED actions are kept forever, which becomes a problem
 * if a buggy consumer or a payload-too-large condition produces a stream of
 * failures over time.
 *
 * Rather than hooking `action_scheduler_default_cleaner_statuses` (which would
 * affect every plugin sharing the AS install), we run a scoped cleaner that
 * deletes only `wp-site-designer` group failures older than the retention
 * threshold. The cleanup itself rides our own scheduler so it shows up in
 * `Tools → Scheduled Actions` and benefits from AS observability.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the internal cleanup task and schedules its daily run.
 */
class Failed_Action_Cleaner {

	const TASK_NAME    = 'cleanup_failed_actions';
	const HOOK         = Scheduled_Tasks::HOOK_PREFIX . self::TASK_NAME;
	const BATCH_SIZE   = 100;
	const DEFAULT_DAYS = 30;

	/**
	 * Hook registration. The daily self-schedule is deferred to scheduling
	 * activation so we don't add a recurring AS action on sites that have
	 * never scheduled anything via us.
	 */
	public static function init(): void {
		// Internal task — scheduled with `_scheduled_by => 0`, so caps must be empty
		// (Task_Runner evaluates caps against user 0 → any non-empty list skips it).
		Scheduled_Tasks::instance()->register_task(
			self::TASK_NAME,
			array( self::class, 'run' ),
			array(),
			array(
				'label'       => 'Clean failed wp-site-designer scheduled actions',
				'description' => 'Deletes FAILED actions in the wp-site-designer group older than the retention window. Runs daily.',
				'internal'    => true,
			)
		);

		// First-time self-schedule on activation.
		\add_action( 'wp_sd_scheduling_activated', array( self::class, 'maybe_schedule' ) );

		// Heal-on-boot: if the recurring action got deleted, re-create it. Only runs
		// on already-activated sites so dormant installs pay nothing. The
		// `as_has_scheduled_action` check inside maybe_schedule keeps it idempotent.
		if ( (bool) \get_option( Scheduled_Tasks::ACTIVATED_OPTION, false ) ) {
			\add_action( 'init', array( self::class, 'maybe_schedule' ), 99 );
		}
	}

	/**
	 * Schedule the daily cleanup if it isn't already pending.
	 */
	public static function maybe_schedule(): void {
		if ( ! \function_exists( 'as_has_scheduled_action' ) || ! \function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		if ( \as_has_scheduled_action( self::HOOK, null, Scheduled_Tasks::GROUP ) ) {
			return;
		}

		// First run a day out so a fresh activation doesn't immediately
		// hammer the queue. Recurs daily thereafter.
		\as_schedule_recurring_action(
			\time() + DAY_IN_SECONDS,
			DAY_IN_SECONDS,
			self::HOOK,
			array( array( '_scheduled_by' => 0 ) ),
			Scheduled_Tasks::GROUP
		);
	}

	/**
	 * Handler. Deletes a batch of failed actions older than the retention
	 * window, scoped to our group.
	 *
	 * Capped at BATCH_SIZE per run so a long backlog doesn't blow request
	 * memory/time — leftovers will be picked up on the next daily run.
	 *
	 * @param array $payload Unused — present to match the task handler contract.
	 */
	public static function run( array $payload ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! \class_exists( '\ActionScheduler' ) || ! \class_exists( '\ActionScheduler_Store' ) ) {
			return;
		}

		$days = (int) \apply_filters( 'wp_sd_failed_action_retention_days', self::DEFAULT_DAYS );
		if ( $days < 1 ) {
			$days = self::DEFAULT_DAYS;
		}

		try {
			$cutoff = new \DateTime( '@' . ( \time() - $days * DAY_IN_SECONDS ) );

			$store = \ActionScheduler::store();
			$ids   = $store->query_actions(
				array(
					'group'        => Scheduled_Tasks::GROUP,
					'status'       => \ActionScheduler_Store::STATUS_FAILED,
					'date'         => $cutoff,
					'date_compare' => '<=',
					'per_page'     => self::BATCH_SIZE,
				)
			);

			if ( ! is_array( $ids ) || empty( $ids ) ) {
				return;
			}

			foreach ( $ids as $id ) {
				try {
					$store->delete_action( (int) $id );
				} catch ( \Throwable $e ) {
					\error_log( sprintf( '[wp_sd_task] failed-cleaner could not delete %d: %s', (int) $id, $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		} catch ( \Throwable $e ) {
			\error_log( sprintf( '[wp_sd_task] failed-cleaner aborted: %s', $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
