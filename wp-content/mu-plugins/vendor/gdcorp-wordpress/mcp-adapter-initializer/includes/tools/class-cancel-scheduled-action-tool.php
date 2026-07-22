<?php
/**
 * Cancel Scheduled Action Tool
 *
 * Cancels a pending scheduled action by its Action Scheduler action id.
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
 * gd-mcp/cancel-scheduled-action
 */
class Cancel_Scheduled_Action_Tool extends Base_Tool {

	const TOOL_ID = 'gd-mcp/cancel-scheduled-action';

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
				'label'               => __( 'Cancel Scheduled Action', 'mcp-adapter-initializer' ),
				'description'         => __( 'Cancel a pending scheduled action by its action id. For recurring schedules, this cancels the pending occurrence and breaks the recurring chain.', 'mcp-adapter-initializer' ),
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
				'action_id' => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => __( 'Action Scheduler action id from list-scheduled-actions or schedule-action.', 'mcp-adapter-initializer' ),
				),
			),
			'required'   => array( 'action_id' ),
		);
	}

	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Cancel scheduled action result', 'mcp-adapter-initializer' ),
			array(
				'cancelled' => array( 'type' => 'integer' ),
			)
		);
	}

	public function execute( array $input ): array {
		$action_id = isset( $input['action_id'] ) ? (int) $input['action_id'] : 0;
		if ( $action_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'action_id must be a positive integer.', 'mcp-adapter-initializer' ),
			);
		}

		$request  = new \WP_REST_Request( 'DELETE', Schedule_Action_Tool::WP_SD_REST_BASE . '/' . $action_id );
		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return Schedule_Action_Tool::format_error_response( $response );
		}

		$data = $response->get_data();
		return array(
			'success'   => true,
			'cancelled' => isset( $data['cancelled'] ) ? (int) $data['cancelled'] : $action_id,
			'message'   => sprintf(
				/* translators: %d: action id */
				__( 'Cancelled scheduled action %d.', 'mcp-adapter-initializer' ),
				$action_id
			),
		);
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
