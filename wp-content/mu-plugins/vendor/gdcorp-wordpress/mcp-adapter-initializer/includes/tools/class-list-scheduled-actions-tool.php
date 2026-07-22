<?php
/**
 * List Scheduled Actions Tool
 *
 * Lists pending scheduled actions queued in wp-extensions, optionally filtered
 * by task name and paginated.
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
 * gd-mcp/list-scheduled-actions
 */
class List_Scheduled_Actions_Tool extends Base_Tool {

	const TOOL_ID = 'gd-mcp/list-scheduled-actions';

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
				'label'               => __( 'List Scheduled Actions', 'mcp-adapter-initializer' ),
				'description'         => __( 'List pending scheduled actions queued in wp-extensions. Returns action IDs, scheduled times, payloads, and pagination metadata.', 'mcp-adapter-initializer' ),
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
				'task'     => array(
					'type'        => 'string',
					'description' => __( 'Optional task-name filter. Defaults to all tasks in the wp-site-designer group.', 'mcp-adapter-initializer' ),
				),
				'page'     => array(
					'type'        => 'integer',
					'description' => __( 'Page number for pending scheduled actions.', 'mcp-adapter-initializer' ),
					'default'     => 1,
					'minimum'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => __( 'Maximum number of pending scheduled actions to return per page.', 'mcp-adapter-initializer' ),
					'default'     => 100,
					'minimum'     => 1,
					'maximum'     => 200,
				),
			),
		);
	}

	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Pending scheduled actions', 'mcp-adapter-initializer' ),
			array(
				'actions'     => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array( 'type' => 'integer' ),
							'hook'         => array( 'type' => 'string' ),
							'scheduled_at' => array( 'type' => 'string' ),
							'payload'      => array( 'type' => 'object' ),
						),
					),
				),
				'page'        => array( 'type' => 'integer' ),
				'per_page'    => array( 'type' => 'integer' ),
				'total'       => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
			)
		);
	}

	public function execute( array $input ): array {
		$task     = isset( $input['task'] ) && '' !== $input['task'] ? (string) $input['task'] : '';
		$page     = max( 1, isset( $input['page'] ) ? (int) $input['page'] : 1 );
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 100;
		$per_page = $per_page > 0 ? min( 200, $per_page ) : 100;

		$request = new \WP_REST_Request( 'GET', Schedule_Action_Tool::WP_SD_REST_BASE );
		if ( '' !== $task ) {
			$request->set_param( 'task', $task );
		}
		$request->set_param( 'page', $page );
		$request->set_param( 'per_page', $per_page );

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return Schedule_Action_Tool::format_error_response( $response );
		}

		$data        = $response->get_data();
		$actions     = ( isset( $data['actions'] ) && is_array( $data['actions'] ) ) ? $data['actions'] : array();
		$page        = isset( $data['page'] ) ? (int) $data['page'] : $page;
		$per_page    = isset( $data['per_page'] ) ? (int) $data['per_page'] : $per_page;
		$total       = isset( $data['total'] ) ? (int) $data['total'] : count( $actions );
		$total_pages = isset( $data['total_pages'] ) ? (int) $data['total_pages'] : 1;

		return array(
			'success'     => true,
			'actions'     => $actions,
			'page'        => $page,
			'per_page'    => $per_page,
			'total'       => $total,
			'total_pages' => $total_pages,
			'message'     => sprintf(
				/* translators: 1: count of pending actions on the current page, 2: total count of pending actions */
				_n( 'Found %1$d pending scheduled action (%2$d total).', 'Found %1$d pending scheduled actions (%2$d total).', count( $actions ), 'mcp-adapter-initializer' ),
				count( $actions ),
				$total
			),
		);
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
