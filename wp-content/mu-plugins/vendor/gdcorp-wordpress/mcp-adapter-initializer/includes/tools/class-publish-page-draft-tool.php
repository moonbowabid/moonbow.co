<?php
/**
 * Publish Page Draft Tool Class
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
 * Publish Page Draft Tool
 *
 * Publishes a draft page by merging its content into the original published page and deleting the draft.
 */
class Publish_Page_Draft_Tool extends Base_Tool {

	const TOOL_ID = 'gd-mcp/publish-page-draft';

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
	 * Register the ability.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( function_exists( 'wp_register_ability' ) ) {
			wp_register_ability(
				self::TOOL_ID,
				array(
					'label'               => __( 'Publish Page Draft', 'mcp-adapter-initializer' ),
					'description'         => __( 'Publishes a draft page by merging its content into the original published page and deleting the draft.', 'mcp-adapter-initializer' ),
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
				'draft_id'     => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => __( 'The ID of the draft page to publish.', 'mcp-adapter-initializer' ),
				),
				'force_delete' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to permanently delete the draft (true) or move it to trash so it can be restored (false). Defaults to false.', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
			),
			'required'   => array( 'draft_id' ),
		);
	}

	/**
	 * @return array
	 */
	private function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Result of publishing a page draft.', 'mcp-adapter-initializer' ),
			array(
				'original_id' => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the original page that was updated.', 'mcp-adapter-initializer' ),
				),
			)
		);
	}

	/**
	 * Execute the tool.
	 *
	 * @param array $input Input parameters.
	 * @return array Execution result.
	 */
	public function execute( array $input ): array {
		$draft_id = isset( $input['draft_id'] ) ? absint( $input['draft_id'] ) : 0;

		if ( $draft_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'A valid draft page ID is required.', 'mcp-adapter-initializer' ),
			);
		}

		$post = get_post( $draft_id );

		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => __( 'The requested draft page does not exist.', 'mcp-adapter-initializer' ),
			);
		}

		if ( 'page' !== $post->post_type ) {
			return array(
				'success' => false,
				'message' => __( 'Only page drafts can be published.', 'mcp-adapter-initializer' ),
			);
		}

		if ( 'draft' !== $post->post_status ) {
			return array(
				'success' => false,
				'message' => __( 'Only posts in draft status can be published as a Site Designer draft.', 'mcp-adapter-initializer' ),
			);
		}

		$draft_of_raw = get_post_meta( $draft_id, Draft_Page_Helper::META_DRAFT_OF, true );
		if ( '' === $draft_of_raw || false === $draft_of_raw ) {
			return array(
				'success' => false,
				'message' => __( 'The page is not a Site Designer draft copy.', 'mcp-adapter-initializer' ),
			);
		}

		$original_id = absint( $draft_of_raw );
		if ( $original_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'The draft is missing a valid original page reference.', 'mcp-adapter-initializer' ),
			);
		}

		$force_delete = array_key_exists( 'force_delete', $input ) ? (bool) $input['force_delete'] : false;

		$result = Draft_Page_Helper::publish_draft( $draft_id, $force_delete );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success'     => true,
			'original_id' => $original_id,
			'message'     => __( 'Draft published successfully.', 'mcp-adapter-initializer' ),
		);
	}
}
