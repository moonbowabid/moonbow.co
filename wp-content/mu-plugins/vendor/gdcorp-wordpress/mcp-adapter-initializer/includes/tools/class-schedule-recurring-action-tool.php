<?php
/**
 * Schedule Recurring Action Tool
 *
 * Same as Schedule_Action_Tool plus an interval. Schedules a recurring run of
 * an MCP tool against the wp-extensions `mcp_tool_call` task.
 *
 * Validation policy: thin queue. Caller owns input shape; the underlying
 * ability validates at fire time.
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
 * gd-mcp/schedule-recurring-action
 */
class Schedule_Recurring_Action_Tool extends Base_Tool {

	const TOOL_ID = 'gd-mcp/schedule-recurring-action';

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
				'label'               => __( 'Schedule Recurring Action', 'mcp-adapter-initializer' ),
				'description'         => __( 'Schedule a tool to run repeatedly at a fixed interval. Caller owns the (tool_id, input) pair; the underlying ability\'s input_schema validates at fire time. Supply interval_seconds to define recurrence.', 'mcp-adapter-initializer' ),
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
				'scheduled_at'     => array(
					'type'        => 'string',
					'description' => __( 'When the first occurrence runs, UTC. Format: YYYY-MM-DD HH:MM:SS. Must be in the future.', 'mcp-adapter-initializer' ),
				),
				'interval_seconds' => array(
					'type'        => 'integer',
					'minimum'     => 60,
					'description' => __( 'Seconds between occurrences. Common values: 3600 (hourly), 86400 (daily), 604800 (weekly).', 'mcp-adapter-initializer' ),
				),
				'target'           => array(
					'type'        => 'object',
					'description' => __( 'The tool to schedule and its input.', 'mcp-adapter-initializer' ),
					'properties'  => array(
						'tool'  => array(
							'type'        => 'string',
							'description' => __( 'MCP tool id to invoke at each fire time.', 'mcp-adapter-initializer' ),
						),
						'input' => array(
							'type'        => 'object',
							'description' => __( 'Input passed to the tool at fire time.', 'mcp-adapter-initializer' ),
						),
					),
					'required'    => array( 'tool', 'input' ),
				),
			),
			'required'   => array( 'scheduled_at', 'interval_seconds', 'target' ),
		);
	}

	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Schedule recurring action result', 'mcp-adapter-initializer' ),
			array(
				'action_id'        => array( 'type' => 'integer' ),
				'tool'             => array( 'type' => 'string' ),
				'scheduled_at'     => array( 'type' => 'string' ),
				'interval_seconds' => array( 'type' => 'integer' ),
			)
		);
	}

	public function execute( array $input ): array {
		$target           = isset( $input['target'] ) && is_array( $input['target'] ) ? $input['target'] : array();
		$tool_id          = isset( $target['tool'] ) ? (string) $target['tool'] : '';
		$tool_input       = isset( $target['input'] ) && is_array( $target['input'] ) ? $target['input'] : array();
		$scheduled_at     = isset( $input['scheduled_at'] ) ? (string) $input['scheduled_at'] : '';
		$interval_seconds = isset( $input['interval_seconds'] ) ? (int) $input['interval_seconds'] : 0;

		if ( '' === $tool_id ) {
			return array(
				'success' => false,
				'message' => __( 'target.tool is required.', 'mcp-adapter-initializer' ),
			);
		}
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
		if ( $interval_seconds < 60 ) {
			return array(
				'success' => false,
				'message' => __( 'interval_seconds must be at least 60.', 'mcp-adapter-initializer' ),
			);
		}

		$payload = array(
			'tool_id' => $tool_id,
			'input'   => $tool_input,
		);

		$request = new \WP_REST_Request( 'POST', Schedule_Action_Tool::WP_SD_REST_BASE );
		$request->set_param( 'task', Schedule_Action_Tool::WP_SD_TASK_NAME );
		$request->set_param( 'payload', $payload );
		$request->set_param( 'scheduled_at', $scheduled_at );
		$request->set_param( 'interval_seconds', $interval_seconds );

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return Schedule_Action_Tool::format_error_response( $response );
		}

		$data = $response->get_data();
		return array(
			'success'          => true,
			'action_id'        => isset( $data['action_id'] ) ? (int) $data['action_id'] : 0,
			'tool'             => $tool_id,
			'scheduled_at'     => isset( $data['scheduled_at'] ) ? (string) $data['scheduled_at'] : '',
			'interval_seconds' => isset( $data['interval'] ) ? (int) $data['interval'] : $interval_seconds,
			'message'          => sprintf(
				/* translators: 1: tool id, 2: interval seconds, 3: scheduled time, 4: action id */
				__( 'Scheduled %1$s every %2$d seconds starting at %3$s (action ID %4$d).', 'mcp-adapter-initializer' ),
				$tool_id,
				$interval_seconds,
				$data['scheduled_at'] ?? $scheduled_at,
				$data['action_id'] ?? 0
			),
		);
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
