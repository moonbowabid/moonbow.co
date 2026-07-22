<?php
/**
 * Scheduled Tasks — registrar + Action Scheduler facade.
 *
 * Foundation for scheduling deferred work from wp-extensions consumers.
 * Tasks are registered with a name, a handler, and the capabilities the
 * handler requires. Scheduling emits an Action Scheduler job that, when
 * the queue runs, invokes the handler under a system-admin identity via
 * Task_Runner.
 *
 * The capability set recorded here is enforced at run time (defence in
 * depth). Consumers MUST still authorise their callers before scheduling.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Scheduling;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton registrar + AS facade.
 */
class Scheduled_Tasks {

	/**
	 * Hook prefix. Concrete hook is `wp_sd_task_<name>`.
	 */
	const HOOK_PREFIX = 'wp_sd_task_';

	/**
	 * AS group. Distinct from `gd-mcp` (owned by mcp-adapter-initializer).
	 */
	const GROUP = 'wp-site-designer';

	/**
	 * Maximum size of the JSON-encoded args array AS will store.
	 *
	 * AS persists args via `wp_json_encode( $action->get_args() )` into the
	 * `extended_args` column, which is `varchar(8000)`. We cap at 7500 bytes
	 * to leave headroom for the `[ $payload ]` array wrapper, the
	 * `_scheduled_by` stamp, and any future runner-stamped metadata so that
	 * a payload accepted here doesn't get silently truncated by MySQL.
	 *
	 * If a real consumer ever needs larger payloads, the path is to offload
	 * the body to a wp_options row (autoload=no) and put the option key in
	 * the AS args; do NOT use transients (Redis-backed transients can be
	 * dropped on cache restart, leaving scheduled actions without payload).
	 */
	const MAX_PAYLOAD_BYTES = 7500;

	/**
	 * Autoloaded option flag set on first successful schedule.
	 */
	const ACTIVATED_OPTION = 'wp_sd_scheduling_activated';

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered tasks keyed by name.
	 *
	 * @var array<string, array{handler: callable, caps: array<int, string>, meta: array}>
	 */
	private array $tasks = array();

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {}

	/**
	 * Get the singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a task handler. Idempotent — re-registering the same name is a no-op.
	 *
	 * @param string             $name    Task name (used in hook `wp_sd_task_{$name}`).
	 * @param callable           $handler Receives the assoc payload (with `_`-prefix keys stripped).
	 * @param array<int, string> $caps    Capabilities required at run time.
	 * @param array              $meta    Optional metadata exposed by the catalog endpoint:
	 *                                    label (string), description (string), payload_schema (array),
	 *                                    internal (bool — hide from REST catalog/list and reject
	 *                                    REST scheduling; for system-owned tasks like cleanup jobs).
	 */
	public function register_task( string $name, callable $handler, array $caps = array(), array $meta = array() ): void {
		if ( '' === $name || isset( $this->tasks[ $name ] ) ) {
			return;
		}

		$this->tasks[ $name ] = array(
			'handler' => $handler,
			'caps'    => array_values( array_filter( array_map( 'strval', $caps ) ) ),
			'meta'    => array(
				'label'          => isset( $meta['label'] ) ? (string) $meta['label'] : $name,
				'description'    => isset( $meta['description'] ) ? (string) $meta['description'] : '',
				'payload_schema' => ( isset( $meta['payload_schema'] ) && is_array( $meta['payload_schema'] ) )
					? $meta['payload_schema']
					: array( 'type' => 'object' ),
				'internal'       => ! empty( $meta['internal'] ),
			),
		);

		$self = $this;
		\add_action(
			self::HOOK_PREFIX . $name,
			static function ( $payload ) use ( $self, $name ): void {
				$entry = $self->get_task( $name );
				if ( null === $entry ) {
					return;
				}
				Task_Runner::run(
					$name,
					$entry['handler'],
					$entry['caps'],
					is_array( $payload ) ? $payload : array()
				);
			},
			10,
			1
		);
	}

	/**
	 * Look up a registered task by name.
	 *
	 * @param string $name Task name.
	 * @return array{handler: callable, caps: array<int, string>, meta: array}|null
	 */
	public function get_task( string $name ): ?array {
		return $this->tasks[ $name ] ?? null;
	}

	/**
	 * Catalog of registered tasks (without handler callables — safe for REST exposure).
	 *
	 * Internal tasks are excluded — they're system-owned (e.g. the failed-action
	 * cleaner) and not callable via REST, so listing them in the catalog would
	 * be misleading.
	 *
	 * @return array<int, array{name:string, label:string, description:string, payload_schema:array, caps:array<int,string>}>
	 */
	public function get_catalog(): array {
		$out = array();
		foreach ( $this->tasks as $name => $entry ) {
			if ( ! empty( $entry['meta']['internal'] ) ) {
				continue;
			}
			$out[] = array(
				'name'           => (string) $name,
				'label'          => (string) ( $entry['meta']['label'] ?? $name ),
				'description'    => (string) ( $entry['meta']['description'] ?? '' ),
				'payload_schema' => is_array( $entry['meta']['payload_schema'] ?? null )
					? $entry['meta']['payload_schema']
					: array( 'type' => 'object' ),
				'caps'           => $entry['caps'],
			);
		}
		return $out;
	}

	/**
	 * Whether a task is registered.
	 *
	 * @param string $name Task name.
	 */
	public function has_task( string $name ): bool {
		return isset( $this->tasks[ $name ] );
	}

	/**
	 * Whether the given task is registered as `internal` (system-owned, hidden
	 * from REST catalog/list, not schedulable via REST).
	 *
	 * @param string $name Task name.
	 */
	public function is_internal_task( string $name ): bool {
		return ! empty( $this->tasks[ $name ]['meta']['internal'] );
	}

	/**
	 * Hooks of all internal tasks. Used to filter them out of the pending list.
	 *
	 * @return array<int, string>
	 */
	private function get_internal_hooks(): array {
		$out = array();
		foreach ( $this->tasks as $name => $entry ) {
			if ( ! empty( $entry['meta']['internal'] ) ) {
				$out[] = self::HOOK_PREFIX . $name;
			}
		}
		return $out;
	}

	/**
	 * Schedule a task.
	 *
	 * @param string   $name      Registered task name. Unknown names are rejected.
	 * @param array    $payload   Assoc array passed as the single AS hook argument.
	 *                            Keys prefixed `_` are reserved for runner metadata.
	 * @param int      $timestamp Unix timestamp (UTC). Past timestamps run on next AS claim.
	 * @param int|null $interval  Seconds between runs. Null = single-shot.
	 * @return int|null Action ID, or null on failure.
	 */
	public function schedule( string $name, array $payload, int $timestamp, ?int $interval = null ): ?int {
		if ( ! $this->has_task( $name ) ) {
			\error_log( sprintf( '[wp_sd_task] schedule failed: unknown task "%s"', $name ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Lazy-load AS on first schedule. The MU plugin (or other consumer) listens
		// for this action and require_once's the bootstrap. No-op if AS is already
		// loaded.
		\do_action( 'wp_sd_ensure_action_scheduler' );

		if ( ! \function_exists( 'as_schedule_single_action' ) ) {
			\error_log( '[wp_sd_task] schedule failed: Action Scheduler not available' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Past-timestamp guard. Both REST and PHP callers hit this gate so semantics
		// don't drift between entry points. REST surfaces a friendlier 400 before
		// reaching here; PHP callers see null + log.
		$now = (int) \gmdate( 'U' );
		if ( $timestamp <= $now ) {
			\error_log( sprintf( '[wp_sd_task] schedule failed: timestamp %d is in the past for "%s"', $timestamp, $name ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Minimum recurrence is 60 seconds — matches Action Scheduler's practical floor
		// and prevents accidental tight loops.
		if ( null !== $interval && $interval < 60 ) {
			\error_log( sprintf( '[wp_sd_task] schedule failed: interval %d below minimum (60s) for "%s"', $interval, $name ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Stamp the scheduling user so Task_Runner can run the handler under their
		// identity. Callers may pre-set this (e.g. when scheduling on behalf of a
		// specific user from a CLI/system context).
		if ( ! isset( $payload['_scheduled_by'] ) ) {
			$payload['_scheduled_by'] = (int) \get_current_user_id();
		}

		$hook = self::HOOK_PREFIX . $name;
		$args = array( $payload );

		// Size guard. AS will silently truncate to varchar(8000) at the DB layer;
		// reject loudly here instead so the caller can surface a real error. The
		// REST controller pre-checks before reaching this point so REST callers
		// see a friendly 413 — this branch covers PHP callers and acts as
		// defense-in-depth if the constants ever drift.
		$encoded_size = self::measure_args_bytes( $args );
		if ( $encoded_size > self::MAX_PAYLOAD_BYTES ) {
			\error_log( sprintf( '[wp_sd_task] schedule failed: payload %d bytes exceeds max %d for "%s"', $encoded_size, self::MAX_PAYLOAD_BYTES, $name ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Dedup: if an identical pending action already exists in our group, return its ID
		// instead of enqueuing a second one. AS matches args via serialized comparison, so
		// "identical" means same task + same payload (including the same _scheduled_by).
		if ( \function_exists( 'as_get_scheduled_actions' ) ) {
			$existing = \as_get_scheduled_actions(
				array(
					'hook'     => $hook,
					'args'     => $args,
					'group'    => self::GROUP,
					'status'   => 'pending',
					'per_page' => 1,
				),
				'ids'
			);
			if ( is_array( $existing ) && ! empty( $existing ) ) {
				return (int) reset( $existing );
			}
		}

		if ( null === $interval ) {
			$id = \as_schedule_single_action( $timestamp, $hook, $args, self::GROUP );
		} else {
			$id = \as_schedule_recurring_action( $timestamp, $interval, $hook, $args, self::GROUP );
		}

		if ( $id ) {
			$this->mark_activated();
			return (int) $id;
		}
		return null;
	}

	/**
	 * Flip the activation flag on first successful schedule. Boot-time AS load
	 * keys off this option, and the failed-action cleaner self-schedules here
	 * so it only exists on sites that have actually used scheduling.
	 *
	 * Idempotent — every call after the first is a noop.
	 */
	private function mark_activated(): void {
		if ( (bool) \get_option( self::ACTIVATED_OPTION, false ) ) {
			return;
		}
		\update_option( self::ACTIVATED_OPTION, 1, true );
		\do_action( 'wp_sd_scheduling_activated' );
	}

	/**
	 * Cancel a pending action by ID.
	 *
	 * Refuses to cancel anything that isn't currently `pending` — completed,
	 * failed, in-progress, and already-cancelled actions all return false so
	 * callers can surface a clear "not cancellable" response. Also returns
	 * false when AS isn't loaded or the action ID does not exist (AS throws
	 * InvalidArgumentException from get_status() in that case).
	 *
	 * @param int $action_id Action Scheduler action ID.
	 */
	public function cancel( int $action_id ): bool {
		if ( ! \class_exists( '\ActionScheduler' ) || ! \class_exists( '\ActionScheduler_Store' ) ) {
			return false;
		}

		try {
			$store  = \ActionScheduler::store();
			$status = $store->get_status( $action_id );

			if ( \ActionScheduler_Store::STATUS_PENDING !== $status ) {
				return false;
			}

			$store->cancel_action( $action_id );

			// Verify the cancellation took effect — some store implementations
			// can no-op silently. If the status is still pending after the call,
			// treat it as a failure.
			return \ActionScheduler_Store::STATUS_PENDING !== $store->get_status( $action_id );
		} catch ( \Throwable $e ) {
			\error_log( sprintf( '[wp_sd_task] cancel failed for action %d: %s', $action_id, $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}
	}

	/**
	 * List pending actions in our group, optionally filtered by task name.
	 *
	 * @param string|null $name     Optional task name to filter by.
	 * @param int         $per_page Page size. Clamped to [1, 200]; default 100.
	 * @param int         $page     1-based page number. Clamped to >= 1; default 1.
	 * @return array<int, array{id:int, hook:string, scheduled_at:string, payload:array}>
	 */
	public function list_pending( ?string $name = null, int $per_page = 100, int $page = 1 ): array {
		if ( ! \function_exists( 'as_get_scheduled_actions' ) ) {
			return array();
		}

		// Internal tasks are not exposed through this listing — querying for one
		// by name returns empty rather than leaking its existence.
		if ( null !== $name && $this->is_internal_task( $name ) ) {
			return array();
		}

		$per_page = max( 1, min( 200, $per_page ) );
		$page     = max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;

		$query = array(
			'group'    => self::GROUP,
			'status'   => 'pending',
			'per_page' => $per_page,
			'offset'   => $offset,
		);
		if ( null !== $name ) {
			$query['hook'] = self::HOOK_PREFIX . $name;
		}

		$actions = \as_get_scheduled_actions( $query, 'OBJECT' );
		if ( ! is_array( $actions ) ) {
			return array();
		}

		// AS query does not support hook negation, so when no explicit task
		// filter is given we drop internal-task rows in PHP. The page may
		// therefore return slightly fewer than per_page items when an internal
		// action lands on it; acceptable trade-off for the small (typically
		// single-row) internal footprint.
		$internal_hooks = ( null === $name ) ? $this->get_internal_hooks() : array();

		$out = array();
		foreach ( $actions as $id => $action ) {
			$hook = method_exists( $action, 'get_hook' ) ? (string) $action->get_hook() : '';
			if ( '' !== $hook && in_array( $hook, $internal_hooks, true ) ) {
				continue;
			}

			$schedule = method_exists( $action, 'get_schedule' ) ? $action->get_schedule() : null;
			$next     = ( $schedule && method_exists( $schedule, 'get_date' ) ) ? $schedule->get_date() : null;

			$args    = method_exists( $action, 'get_args' ) ? $action->get_args() : array();
			$payload = ( isset( $args[0] ) && is_array( $args[0] ) ) ? $args[0] : array();

			$out[] = array(
				'id'           => (int) $id,
				'hook'         => $hook,
				'scheduled_at' => $next ? $next->format( 'c' ) : '',
				'payload'      => $payload,
			);
		}
		return $out;
	}

	/**
	 * Count pending actions in our group, optionally filtered by task name.
	 *
	 * Used by the REST controller to populate `X-WP-Total` / `total_pages`
	 * without having to fetch every page.
	 *
	 * @param string|null $name Optional task name to filter by.
	 */
	public function count_pending( ?string $name = null ): int {
		if ( ! \class_exists( '\ActionScheduler' ) || ! \class_exists( '\ActionScheduler_Store' ) ) {
			return 0;
		}

		// Match list_pending: internal tasks are invisible.
		if ( null !== $name && $this->is_internal_task( $name ) ) {
			return 0;
		}

		$query = array(
			'group'  => self::GROUP,
			'status' => 'pending',
		);
		if ( null !== $name ) {
			$query['hook'] = self::HOOK_PREFIX . $name;
		}

		try {
			$store = \ActionScheduler::store();
			$total = (int) $store->query_actions( $query, 'count' );

			// When listing across all tasks, subtract internal-hook counts so
			// the user-facing total stays consistent with what list_pending
			// returns. Bounded by the (small) number of internal tasks.
			if ( null === $name ) {
				foreach ( $this->get_internal_hooks() as $hook ) {
					$internal = (int) $store->query_actions(
						array(
							'group'  => self::GROUP,
							'status' => 'pending',
							'hook'   => $hook,
						),
						'count'
					);
					$total   -= $internal;
				}
			}

			return max( 0, $total );
		} catch ( \Throwable $e ) {
			\error_log( sprintf( '[wp_sd_task] count_pending failed: %s', $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return 0;
		}
	}

	/**
	 * Measure the JSON-encoded byte length of an args array, the way AS does.
	 *
	 * Mirrors `ActionScheduler_DBStore::save_action()` which stores
	 * `wp_json_encode( $action->get_args() )` into the `extended_args` column.
	 * On encoding failure (e.g. non-UTF8 binary) returns PHP_INT_MAX so the
	 * size guard rejects the payload rather than letting it through.
	 *
	 * @param array $args Args array as it would be passed to AS (i.e. `[ $payload ]`).
	 */
	public static function measure_args_bytes( array $args ): int {
		$encoded = \function_exists( 'wp_json_encode' ) ? \wp_json_encode( $args ) : \json_encode( $args ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		if ( false === $encoded ) {
			return PHP_INT_MAX;
		}
		return strlen( (string) $encoded );
	}

	/**
	 * Reset all registered tasks. Test-only.
	 *
	 * @internal
	 */
	public function reset_for_testing(): void {
		$this->tasks = array();
	}
}
