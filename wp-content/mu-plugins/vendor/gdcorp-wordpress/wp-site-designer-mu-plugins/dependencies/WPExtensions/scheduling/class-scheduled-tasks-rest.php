<?php
/**
 * REST controller for the wp-extensions task scheduler.
 *
 * Routes under `wp-site-designer/v1/scheduled-tasks`. The cross-plugin contract
 * for callers (e.g. mcp-adapter-initializer) — call these endpoints via
 * `rest_do_request()` rather than referencing internal classes.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling;

use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Utils\Rate_Limiter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller. Init via Scheduling_Bootstrap.
 */
class Scheduled_Tasks_REST {

	const REST_NAMESPACE = 'wp-site-designer/v1';
	const REST_BASE      = '/scheduled-tasks';
	const RATE_BUDGET    = 60;
	const RATE_WINDOW    = 60;

	/**
	 * Hook REST registration.
	 */
	public static function init(): void {
		$instance = new self();
		\add_action( 'rest_api_init', array( $instance, 'register_routes' ) );
	}

	/**
	 * Register the four routes plus the catalog route.
	 */
	public function register_routes(): void {
		\register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_pending' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'task'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 100,
							'minimum' => 1,
							'maximum' => 200,
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'task'             => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'payload'          => array(
							'type'              => 'object',
							'required'          => false,
							/**
							 * Validate the supplied payload against the registered task's payload_schema.
							 *
							 * Each task registers a JSON Schema via `meta.payload_schema`; if present it's
							 * enforced here using core's WP_REST_Server validator. Tasks without a schema
							 * accept any object.
							 */
							'validate_callback' => array( $this, 'validate_payload_against_task_schema' ),
						),
						'scheduled_at'     => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'interval_seconds' => array(
							'type'     => 'integer',
							'required' => false,
							'minimum'  => 60,
						),
					),
				),
			)
		);

		\register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE . '/catalog',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'catalog' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		\register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 1,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission gate: requires manage_options + per-user rate limit.
	 *
	 * The rate limiter is best-effort: under concurrent requests with a persistent
	 * object cache (Redis/Memcached) the get-then-set increment in Rate_Limiter is
	 * racey, so a determined caller can exceed the budget by a small amount inside
	 * a single window. The cap-check above is the real authorization gate; the
	 * rate limit is anti-spam, not anti-abuse. See class-rate-limiter.php for
	 * details and the README for the full rationale.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return false;
		}

		$identifier = 'wp_sd_scheduled_tasks_' . \get_current_user_id();
		if ( ! Rate_Limiter::check( $identifier, self::RATE_BUDGET, self::RATE_WINDOW ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				'Too many scheduling requests; try again shortly.',
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * REST validate_callback for the `payload` arg.
	 *
	 * Looks up the registered task (by the request's `task` param) and, if it carries
	 * a `payload_schema`, validates the supplied payload against it via core's
	 * `rest_validate_value_from_schema()`. Tasks without a schema accept any object.
	 *
	 * Returns true on pass, WP_Error on fail (REST surfaces as a 400).
	 *
	 * @param mixed            $value   Submitted payload value.
	 * @param \WP_REST_Request $request Current REST request.
	 * @return bool|\WP_Error
	 */
	public function validate_payload_against_task_schema( $value, \WP_REST_Request $request ) {
		$task = (string) $request->get_param( 'task' );
		if ( '' === $task ) {
			return true; // task validation handles the empty case.
		}

		$entry = Scheduled_Tasks::instance()->get_task( $task );
		if ( null === $entry ) {
			return true; // unknown task — let create() return the clearer "unknown_task" error.
		}

		$schema = $entry['meta']['payload_schema'] ?? null;
		if ( ! is_array( $schema ) || empty( $schema ) ) {
			return true;
		}

		// Treat missing payload as empty object so JSON-schema "required" enforcement still fires.
		$candidate = is_array( $value ) ? $value : array();

		return \rest_validate_value_from_schema( $candidate, $schema, 'payload' );
	}

	// ── handlers ────────────────────────────────────────────────────────

	/**
	 * GET /scheduled-tasks
	 *
	 * @param \WP_REST_Request $request REST request.
	 */
	public function list_pending( \WP_REST_Request $request ): \WP_REST_Response {
		$task = $request->get_param( 'task' );
		$task = is_string( $task ) && '' !== $task ? $task : null;

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$per_page = $per_page > 0 ? min( 200, $per_page ) : 100;

		$model       = Scheduled_Tasks::instance();
		$actions     = $model->list_pending( $task, $per_page, $page );
		$total       = $model->count_pending( $task );
		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		$response = new \WP_REST_Response(
			array(
				'actions'     => $actions,
				'page'        => $page,
				'per_page'    => $per_page,
				'total'       => $total,
				'total_pages' => $total_pages,
			),
			200
		);
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * POST /scheduled-tasks
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create( \WP_REST_Request $request ) {
		$task             = (string) $request->get_param( 'task' );
		$payload          = $request->get_param( 'payload' );
		$payload          = is_array( $payload ) ? $payload : array();
		$scheduled_at_str = (string) $request->get_param( 'scheduled_at' );
		$interval         = $request->get_param( 'interval_seconds' );
		$interval         = ( null === $interval || '' === $interval ) ? null : (int) $interval;

		if ( ! Scheduled_Tasks::instance()->has_task( $task ) ) {
			return new \WP_Error(
				'unknown_task',
				sprintf( 'Unknown task "%s". Call /scheduled-tasks/catalog for the list of registered tasks.', $task ),
				array( 'status' => 400 )
			);
		}

		// Internal tasks are system-owned and not callable via REST. Treat the
		// request as if the task doesn't exist (the catalog also hides them) so
		// REST callers can't probe internal task names.
		if ( Scheduled_Tasks::instance()->is_internal_task( $task ) ) {
			return new \WP_Error(
				'unknown_task',
				sprintf( 'Unknown task "%s". Call /scheduled-tasks/catalog for the list of registered tasks.', $task ),
				array( 'status' => 400 )
			);
		}

		try {
			$dt = new \DateTimeImmutable( $scheduled_at_str, new \DateTimeZone( 'UTC' ) );
		} catch ( \Throwable $e ) {
			return new \WP_Error(
				'invalid_scheduled_at',
				'Invalid scheduled_at format. Use YYYY-MM-DD HH:MM:SS in UTC.',
				array( 'status' => 400 )
			);
		}

		$timestamp = $dt->getTimestamp();
		// Compare two Unix epoch values explicitly. `time()` and `gmdate('U')` both return
		// seconds-since-epoch (UTC); we use the gmdate form to make the UTC contract obvious
		// next to a $dt that was explicitly parsed in UTC.
		$now = (int) \gmdate( 'U' );
		if ( $timestamp <= $now ) {
			return new \WP_Error(
				'scheduled_at_in_past',
				sprintf(
					'scheduled_at must be in the future. Got "%s"; current time is "%s" (UTC).',
					\gmdate( 'c', $timestamp ),
					\gmdate( 'c', $now )
				),
				array( 'status' => 400 )
			);
		}

		// Defense-in-depth: model layer also rejects intervals < 60. We surface a friendlier
		// 400 here so the API caller doesn't have to inspect logs to learn why the call returned null.
		if ( null !== $interval && $interval < 60 ) {
			return new \WP_Error(
				'invalid_interval',
				'interval_seconds must be at least 60.',
				array( 'status' => 400 )
			);
		}

		// Size guard. AS persists args as JSON in `extended_args` (varchar 8000) — anything
		// larger gets silently truncated by MySQL. Reject up front with a 413 so callers
		// don't have to inspect logs. We measure the same shape the model will pass to AS,
		// including the `_scheduled_by` stamp the model would add if missing.
		$measure_payload = $payload;
		if ( ! isset( $measure_payload['_scheduled_by'] ) ) {
			$measure_payload['_scheduled_by'] = (int) \get_current_user_id();
		}
		$encoded_size = Scheduled_Tasks::measure_args_bytes( array( $measure_payload ) );
		if ( $encoded_size > Scheduled_Tasks::MAX_PAYLOAD_BYTES ) {
			return new \WP_Error(
				'payload_too_large',
				sprintf(
					'payload exceeds the %d-byte limit (got %d bytes JSON-encoded). Action Scheduler stores args in a varchar(8000) column; offload large bodies to wp_options and pass a key here.',
					Scheduled_Tasks::MAX_PAYLOAD_BYTES,
					$encoded_size
				),
				array( 'status' => 413 )
			);
		}

		// Audit/identity metadata (`_scheduled_by`) is stamped by the model so PHP and
		// REST callers behave consistently. AS already records the scheduled time.

		$action_id = Scheduled_Tasks::instance()->schedule( $task, $payload, $timestamp, $interval );

		if ( null === $action_id ) {
			return new \WP_Error(
				'schedule_failed',
				'Failed to schedule task — Action Scheduler may be unavailable.',
				array( 'status' => 500 )
			);
		}

		return new \WP_REST_Response(
			array(
				'action_id'    => $action_id,
				'task'         => $task,
				'scheduled_at' => \gmdate( 'c', $timestamp ),
				'recurring'    => null !== $interval,
				'interval'     => $interval,
			),
			201
		);
	}

	/**
	 * DELETE /scheduled-tasks/{id}
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function cancel( \WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		if ( $id <= 0 ) {
			return new \WP_Error( 'invalid_id', 'id must be a positive integer.', array( 'status' => 400 ) );
		}

		$ok = Scheduled_Tasks::instance()->cancel( $id );
		if ( ! $ok ) {
			return new \WP_Error(
				'cancel_failed',
				sprintf( 'Could not cancel action %d (already complete, never existed, or AS unavailable).', $id ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response( array( 'cancelled' => $id ), 200 );
	}

	/**
	 * GET /scheduled-tasks/catalog
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 */
	public function catalog( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return new \WP_REST_Response(
			array( 'tasks' => Scheduled_Tasks::instance()->get_catalog() ),
			200
		);
	}
}
