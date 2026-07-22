<?php
/**
 * Create Page Draft Tool Class
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
 * Create Page Draft Tool
 *
 * Creates a draft copy of a published page for safe editing.
 */
class Create_Page_Draft_Tool extends Base_Tool {

	const TOOL_ID = 'gd-mcp/create-page-draft';

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
					'label'               => __( 'Create Page Draft', 'mcp-adapter-initializer' ),
					'description'         => __( 'Creates a draft copy of a published page for safe editing without affecting the live site.', 'mcp-adapter-initializer' ),
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
					'description' => __( 'The ID of the published page to create a draft of.', 'mcp-adapter-initializer' ),
				),
				'title'   => array(
					'type'        => 'string',
					'description' => __( 'Optional title override for the draft.', 'mcp-adapter-initializer' ),
				),
				'content' => array(
					'type'        => 'string',
					'description' => __( 'Optional content override for the draft.', 'mcp-adapter-initializer' ),
				),
				'excerpt' => array(
					'type'        => 'string',
					'description' => __( 'Optional excerpt override for the draft.', 'mcp-adapter-initializer' ),
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
			__( 'Result of creating a page draft.', 'mcp-adapter-initializer' ),
			array(
				'draft_id'    => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the created draft page.', 'mcp-adapter-initializer' ),
				),
				'edit_url'    => array(
					'type'        => 'string',
					'description' => __( 'URL to edit the draft in the block editor.', 'mcp-adapter-initializer' ),
				),
				'original_id' => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the original published page.', 'mcp-adapter-initializer' ),
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
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		if ( $post_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'A valid page ID is required.', 'mcp-adapter-initializer' ),
			);
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => __( 'The requested page does not exist.', 'mcp-adapter-initializer' ),
			);
		}

		if ( 'page' !== $post->post_type ) {
			return array(
				'success' => false,
				'message' => __( 'Drafts can only be created for pages (post_type=page).', 'mcp-adapter-initializer' ),
			);
		}

		if ( 'publish' !== $post->post_status ) {
			return array(
				'success' => false,
				'message' => __( 'A draft can only be created from a published page.', 'mcp-adapter-initializer' ),
			);
		}

		if ( Draft_Page_Helper::has_draft( $post_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'A draft version already exists for this page.', 'mcp-adapter-initializer' ),
			);
		}

		$title   = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $post->post_title;
		$content = isset( $input['content'] ) ? wp_kses_post( $input['content'] ) : $post->post_content;
		$excerpt = isset( $input['excerpt'] ) ? sanitize_textarea_field( $input['excerpt'] ) : $post->post_excerpt;

		$draft_data = array(
			'post_title'            => $title,
			'post_content'          => $content,
			'post_excerpt'          => $excerpt,
			'post_status'           => 'draft',
			'post_type'             => 'page',
			'post_author'           => get_current_user_id(),
			'post_parent'           => $post_id,
			'menu_order'            => $post->menu_order,
			'post_password'         => $post->post_password,
			'comment_status'        => $post->comment_status,
			'ping_status'           => $post->ping_status,
			'post_content_filtered' => $post->post_content_filtered,
		);

		$draft_id = wp_insert_post( wp_slash( $draft_data ), true );

		if ( is_wp_error( $draft_id ) ) {
			return array(
				'success' => false,
				'message' => $draft_id->get_error_message(),
			);
		}

		// WordPress clears post_name for draft-status posts in wp_unique_post_slug().
		// Generate a unique slug and set it directly via $wpdb.
		global $wpdb;
		$desired_slug = sanitize_title( $post->post_name . '-draft' );
		$unique_slug  = wp_unique_post_slug( $desired_slug, $draft_id, 'draft', 'page', $post_id );

		// wp_unique_post_slug() returns empty for draft status; fall back with manual collision check.
		if ( empty( $unique_slug ) ) {
			$unique_slug = $desired_slug;
			$suffix      = 2;

			while (
				$wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1",
						$unique_slug,
						$draft_id
					)
				)
			) {
				$unique_slug = $desired_slug . '-' . $suffix;
				++$suffix;
			}
		}

		$updated = $wpdb->update(
			$wpdb->posts,
			array( 'post_name' => $unique_slug ),
			array( 'ID' => $draft_id )
		);

		if ( false === $updated ) {
			wp_delete_post( $draft_id, true );
			return array(
				'success' => false,
				'message' => __( 'Failed to set draft slug.', 'mcp-adapter-initializer' ),
			);
		}

		clean_post_cache( $draft_id );

		Draft_Page_Helper::copy_post_meta( $post_id, $draft_id );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $draft_id, $thumbnail_id );
		}

		update_post_meta( $draft_id, Draft_Page_Helper::META_DRAFT_OF, $post_id );
		update_post_meta( $draft_id, Draft_Page_Helper::META_DRAFT_CREATED, current_time( 'mysql' ) );
		update_post_meta( $post_id, Draft_Page_Helper::META_HAS_DRAFT, $draft_id );

		do_action( 'wp_site_designer_draft_created', $draft_id, $post_id );

		$edit_url = get_edit_post_link( $draft_id, 'raw' );
		$edit_url = is_string( $edit_url ) ? $edit_url : '';

		return array(
			'success'     => true,
			'draft_id'    => $draft_id,
			'edit_url'    => $edit_url,
			'original_id' => $post_id,
			'message'     => __( 'Draft created successfully.', 'mcp-adapter-initializer' ),
		);
	}
}
