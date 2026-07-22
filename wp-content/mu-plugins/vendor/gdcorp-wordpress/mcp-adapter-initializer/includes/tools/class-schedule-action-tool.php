<?php
/**
 * Schedule Action Tool
 *
 * Schedules a single-shot run of an MCP tool. Translates the caller's
 * `tool_id` + `input` into a wp-extensions REST scheduling call against the
 * generic `mcp_tool_call` task. Execution happens entirely inside
 * wp-extensions when Action Scheduler fires.
 *
 * Validation policy: this tool is a thin queue. Per-tool input shape is
 * validated by the caller (the API agent layer owns the typed schemas) and
 * by the underlying ability's own input_schema at fire time. We only check
 * that `tool_id` is non-empty, that `scheduled_at` is provided, and that
 * the named ability actually exists.
 *
 * @package     mcp-adapter-initializer
 * @author      GoDaddy
 * @copyright   2025 GoDaddy
 * @license     GPL-2.0-or-later
 */

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * gd-mcp/schedule-action
 */
class Schedule_Action_Tool extends Base_Tool {

	const TOOL_ID         = 'gd-mcp/schedule-action';
	const WP_SD_REST_BASE = '/wp-site-designer/v1/scheduled-tasks';
	const WP_SD_TASK_NAME = 'mcp_tool_call';

	/** @var self|null */
	private static $instance = null;

	/** @return self */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function get_tool_id(): string {
		return self::TOOL_ID;
	}

	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Schedule Action', 'mcp-adapter-initializer' ),
				'description'         => __( 'Schedule a tool to run at a future UTC time. Caller is responsible for supplying a valid (tool_id, input) pair; the underlying ability\'s own input_schema validates the input at fire time.', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'content-management',
			)
		);
	}

	private function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'scheduled_at' => array(
					'type'        => 'string',
					'description' => __( 'When to run, in UTC. Format: YYYY-MM-DD HH:MM:SS. Must be a future timestamp.', 'mcp-adapter-initializer' ),
				),
				'target'       => array(
					'type'        => 'object',
					'description' => __( 'The tool to schedule and its input.', 'mcp-adapter-initializer' ),
					'properties'  => array(
						'tool'  => array(
							'type'        => 'string',
							'description' => __( 'MCP tool id to invoke at fire time.', 'mcp-adapter-initializer' ),
						),
						'input' => array(
							'type'        => 'object',
							'description' => __( 'Input passed to the tool at fire time. Validated by the tool\'s own input_schema then.', 'mcp-adapter-initializer' ),
						),
					),
					'required'    => array( 'tool', 'input' ),
				),
			),
			'required'   => array( 'scheduled_at', 'target' ),
		);
	}

	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Schedule action result', 'mcp-adapter-initializer' ),
			array(
				'action_id'    => array(
					'type'        => 'integer',
					'description' => __( 'Action Scheduler action ID. Use cancel-scheduled-action with this ID to cancel.', 'mcp-adapter-initializer' ),
				),
				'tool'         => array( 'type' => 'string' ),
				'scheduled_at' => array( 'type' => 'string' ),
			)
		);
	}

	public function execute( array $input ): array {
		$target       = isset( $input['target'] ) && is_array( $input['target'] ) ? $input['target'] : array();
		$tool_id      = isset( $target['tool'] ) ? (string) $target['tool'] : '';
		$tool_input   = isset( $target['input'] ) && is_array( $target['input'] ) ? $target['input'] : array();
		$scheduled_at = isset( $input['scheduled_at'] ) ? (string) $input['scheduled_at'] : '';

		if ( '' === $tool_id ) {
			return array(
				'success' => false,
				'message' => __( 'target.tool is required.', 'mcp-adapter-initializer' ),
			);
		}
		// Fail fast at schedule time when the named ability doesn't exist
		// (e.g. plugin inactive). Avoids queuing an action that no-ops at
		// fire time.
		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( $tool_id ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: tool id */
					__( 'Tool "%s" is not currently available.', 'mcp-adapter-initializer' ),
					$tool_id
				),
			);
		}
		if ( '' === $scheduled_at ) {
			return array(
				'success' => false,
				'message' => __( 'scheduled_at is required.', 'mcp-adapter-initializer' ),
			);
		}

		$payload = array(
			'tool_id' => $tool_id,
			'input'   => $tool_input,
		);

		$request = new \WP_REST_Request( 'POST', self::WP_SD_REST_BASE );
		$request->set_param( 'task', self::WP_SD_TASK_NAME );
		$request->set_param( 'payload', $payload );
		$request->set_param( 'scheduled_at', $scheduled_at );

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return self::format_error_response( $response );
		}

		$data = $response->get_data();
		return array(
			'success'      => true,
			'action_id'    => isset( $data['action_id'] ) ? (int) $data['action_id'] : 0,
			'tool'         => $tool_id,
			'scheduled_at' => isset( $data['scheduled_at'] ) ? (string) $data['scheduled_at'] : '',
			'message'      => sprintf(
				/* translators: 1: tool id, 2: scheduled time, 3: action id */
				__( 'Scheduled %1$s at %2$s (action ID %3$d).', 'mcp-adapter-initializer' ),
				$tool_id,
				$data['scheduled_at'] ?? $scheduled_at,
				$data['action_id'] ?? 0
			),
		);
	}

	/**
	 * Translate a WP_REST_Response error into the tool's failure shape, with a
	 * friendlier message when the wp-extensions backend isn't available.
	 *
	 * @param \WP_REST_Response $response Error response.
	 */
	public static function format_error_response( \WP_REST_Response $response ): array {
		$err = $response->as_error();
		$msg = $err instanceof \WP_Error ? $err->get_error_message() : __( 'Unknown error', 'mcp-adapter-initializer' );

		$status = (int) $response->get_status();
		if ( 404 === $status && false !== strpos( $msg, 'No route was found' ) ) {
			$msg = __( 'Scheduling backend not available — wp-extensions is not installed or active.', 'mcp-adapter-initializer' );
		}

		return array(
			'success' => false,
			'message' => $msg,
		);
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
