<?php
/**
 * Get Page Draft Status Tool Class
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
 * Get Page Draft Status Tool
 *
 * Returns draft relationship information for a page.
 */
class Get_Page_Draft_Status_Tool extends Base_Tool {

	const TOOL_ID = 'gd-mcp/get-page-draft-status';

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * @return void
	 */
	public function register(): void {
		if ( function_exists( 'wp_register_ability' ) ) {
			wp_register_ability(
				self::TOOL_ID,
				array(
					'label'               => __( 'Get Page Draft Status', 'mcp-adapter-initializer' ),
					'description'         => __( 'Returns draft relationship information for a page: whether it is a draft, has a draft, or can have one created.', 'mcp-adapter-initializer' ),
					'input_schema'        => $this->get_input_schema(),
					'output_schema'       => $this->get_output_schema(),
					'execute_callback'    => array( $this, 'execute_with_admin' ),
					'permission_callback' => '__return_true',
					'category'            => 'content-management',
				)
			);
		}
	}

	/**
	 * @return string
	 */
	public function get_tool_id(): string {
		return self::TOOL_ID;
	}

	/**
	 * @return array
	 */
	private function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => __( 'The page ID to check draft status for.', 'mcp-adapter-initializer' ),
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	/**
	 * @return array
	 */
	private function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Draft status information for a page.', 'mcp-adapter-initializer' ),
			array(
				'is_draft'    => array(
					'type'        => 'boolean',
					'description' => __( 'Whether this page is itself a draft copy of another page.', 'mcp-adapter-initializer' ),
				),
				'has_draft'   => array(
					'type'        => 'boolean',
					'description' => __( 'Whether this page has an active draft copy.', 'mcp-adapter-initializer' ),
				),
				'draft_id'    => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'The draft page ID (if has_draft is true or is_draft is true).', 'mcp-adapter-initializer' ),
				),
				'original_id' => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'The original published page ID (if is_draft is true or has_draft is true).', 'mcp-adapter-initializer' ),
				),
				'can_create'  => array(
					'type'        => 'boolean',
					'description' => __( 'Whether a draft can be created for this page.', 'mcp-adapter-initializer' ),
				),
			)
		);
	}

	/**
	 * @param array $input Input parameters.
	 * @return array Execution result.
	 */
	public function execute( array $input ): array {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Page with ID %d not found.', 'mcp-adapter-initializer' ), $post_id ),
			);
		}

		if ( 'page' !== $post->post_type ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Post with ID %d is not a page.', 'mcp-adapter-initializer' ), $post_id ),
			);
		}

		$original_id_raw = get_post_meta( $post_id, Draft_Page_Helper::META_DRAFT_OF, true );
		$original_id     = absint( $original_id_raw );
		if ( $original_id > 0 && 'draft' === $post->post_status ) {
			return array(
				'success'     => true,
				'is_draft'    => true,
				'has_draft'   => false,
				'draft_id'    => $post_id,
				'original_id' => $original_id,
				'can_create'  => false,
			);
		}

		$draft = Draft_Page_Helper::get_draft( $post_id );
		if ( $draft ) {
			return array(
				'success'     => true,
				'is_draft'    => false,
				'has_draft'   => true,
				'draft_id'    => (int) $draft->ID,
				'original_id' => $post_id,
				'can_create'  => false,
			);
		}

		$can_create = 'page' === $post->post_type
			&& 'publish' === $post->post_status;

		return array(
			'success'     => true,
			'is_draft'    => false,
			'has_draft'   => false,
			'draft_id'    => null,
			'original_id' => $post_id,
			'can_create'  => $can_create,
		);
	}
}
