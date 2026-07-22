<?php
/**
 * Task Runner — runs a scheduled task handler under the scheduler's identity.
 *
 * Action Scheduler invokes hook callbacks in cron/ajax contexts where there is
 * no logged-in user. The runner reads the `_scheduled_by` user id stamped onto
 * the payload at schedule time, calls wp_set_current_user() with that id, and
 * enforces the registered capability list against that user. Handler exceptions
 * are caught and logged so the action still appears `complete` in the AS admin
 * screen rather than `failed`.
 *
 * If the scheduling user no longer exists at run time the handler is skipped
 * with a log line — there's no fallback identity. Tasks scheduled from a
 * non-user context (CLI, cron) carry user id 0 and run with no logged-in user,
 * which means any non-empty cap list will fail the gate.
 *
 * Identity is restored after the handler runs (or throws, or fails caps) via
 * a finally block — AS can fire multiple actions in the same PHP request and
 * leaving an elevated identity in place would silently affect subsequent
 * actions (including unrelated plugins') for the rest of the queue run.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless wrapper invoked by registered task closures.
 */
class Task_Runner {

	/**
	 * Run a task handler under the scheduling user's identity, with cap enforcement.
	 *
	 * @param string             $name    Task name (for logging).
	 * @param callable           $handler Handler to invoke with the cleaned payload.
	 * @param array<int, string> $caps    Capabilities required.
	 * @param array              $payload Raw payload; carries `_scheduled_by` (user id);
	 *                                    `_`-prefix keys are stripped before handoff.
	 */
	public static function run( string $name, callable $handler, array $caps, array $payload ): void {
		$user_id = isset( $payload['_scheduled_by'] ) ? (int) $payload['_scheduled_by'] : 0;

		// If a real user id was stamped, validate it still exists. We treat a deleted
		// user as "skip" — there's no sensible fallback identity, and we don't want to
		// silently elevate to a different user.
		if ( $user_id > 0 ) {
			$user = \get_userdata( $user_id );
			if ( ! $user ) {
				\error_log( sprintf( '[wp_sd_task] %s skipped: scheduling user %d no longer exists', $name, $user_id ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return;
			}
		}

		$prior_user_id = (int) \get_current_user_id();

		\wp_set_current_user( $user_id );

		try {
			foreach ( $caps as $cap ) {
				if ( ! \current_user_can( $cap ) ) {
					\error_log( sprintf( '[wp_sd_task] %s skipped: user %d lacks capability "%s"', $name, $user_id, $cap ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					return;
				}
			}

			$clean = self::strip_reserved_keys( $payload );

			try {
				$handler( $clean );
			} catch ( \Throwable $e ) {
				\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'[wp_sd_task] %s handler threw: %s in %s:%d',
						$name,
						$e->getMessage(),
						$e->getFile(),
						$e->getLine()
					)
				);
			}
		} finally {
			// Restore prior identity. wp_set_current_user(0) is the canonical
			// "no logged-in user" state, which is what AS context expects.
			\wp_set_current_user( $prior_user_id );
		}
	}

	/**
	 * Remove reserved (`_`-prefix) keys from a payload before handing to the handler.
	 *
	 * @param array $payload Raw payload.
	 * @return array
	 */
	private static function strip_reserved_keys( array $payload ): array {
		$clean = array();
		foreach ( $payload as $k => $v ) {
			if ( is_string( $k ) && 0 === strpos( $k, '_' ) ) {
				continue;
			}
			$clean[ $k ] = $v;
		}
		return $clean;
	}
}
