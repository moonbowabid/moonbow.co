<?php
/**
 * List Global Styles Revisions Tool
 *
 * @package     mcp-adapter-initializer
 * @author      GoDaddy
 * @copyright   2026 GoDaddy
 * @license     GPL-2.0-or-later
 */

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * List Global Styles Revisions Tool Class
 *
 * Provides functionality to list revisions for a specified
 * wp_global_styles post using the WordPress core REST controller
 * dedicated to global styles revisions (decodes styles/settings JSON
 * and gates on edit_theme_options).
 */
class List_Global_Styles_Revisions_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 */
	const TOOL_ID = 'gd-mcp/list-global-styles-revisions';

	/**
	 * Singleton instance
	 *
	 * @var List_Global_Styles_Revisions_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return List_Global_Styles_Revisions_Tool
	 */
	public static function get_instance(): List_Global_Styles_Revisions_Tool {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Register the list global styles revisions ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'List Global Styles Revisions', 'mcp-adapter-initializer' ),
				'description'         => __( 'Retrieves revisions for a wp_global_styles post', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'theme-management',
			)
		);
	}

	/**
	 * Get the tool identifier
	 *
	 * @return string
	 */
	public function get_tool_id(): string {
		return self::TOOL_ID;
	}

	/**
	 * Get input schema for the tool
	 *
	 * @return array
	 */
	private function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'       => array(
					'type'        => 'integer',
					'description' => __( 'The ID of a wp_global_styles post (obtained from list-global-styles or get-global-styles)', 'mcp-adapter-initializer' ),
					'minimum'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => __( 'Maximum number of items to return', 'mcp-adapter-initializer' ),
					'default'     => 10,
					'minimum'     => 1,
					'maximum'     => 100,
				),
				'page'     => array(
					'type'        => 'integer',
					'description' => __( 'Current page of the collection', 'mcp-adapter-initializer' ),
					'default'     => 1,
					'minimum'     => 1,
				),
			),
			'required'   => array( 'id' ),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Global styles revisions list result', 'mcp-adapter-initializer' ),
			array(
				'revisions'   => array(
					'type'        => 'array',
					'description' => __( 'Array of revision objects', 'mcp-adapter-initializer' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'       => array(
								'type'        => 'integer',
								'description' => __( 'Revision ID', 'mcp-adapter-initializer' ),
							),
							'parent'   => array(
								'type'        => 'integer',
								'description' => __( 'Parent wp_global_styles post ID', 'mcp-adapter-initializer' ),
							),
							'author'   => array(
								'type'        => 'integer',
								'description' => __( 'Author ID', 'mcp-adapter-initializer' ),
							),
							'date'     => array(
								'type'        => 'string',
								'description' => __( 'Revision date', 'mcp-adapter-initializer' ),
							),
							'styles'   => array(
								'type'        => 'object',
								'description' => __( 'Decoded styles object', 'mcp-adapter-initializer' ),
							),
							'settings' => array(
								'type'        => 'object',
								'description' => __( 'Decoded settings object', 'mcp-adapter-initializer' ),
							),
						),
					),
				),
				'total'       => array(
					'type'        => 'integer',
					'description' => __( 'Total number of revisions', 'mcp-adapter-initializer' ),
				),
				'total_pages' => array(
					'type'        => 'integer',
					'description' => __( 'Total number of pages', 'mcp-adapter-initializer' ),
				),
			)
		);
	}

	/**
	 * Execute the list global styles revisions tool
	 *
	 * Uses WP_REST_Global_Styles_Revisions_Controller so caps and JSON
	 * decoding match the rest of the global-styles REST surface.
	 *
	 * @param array $input Input parameters
	 * @return array List of revisions or error
	 */
	public function execute( array $input ): array {
		try {
			$global_styles_id = isset( $input['id'] ) ? (int) $input['id'] : 0;

			if ( $global_styles_id <= 0 ) {
				return array(
					'success' => false,
					'message' => __( 'Global styles post ID is required', 'mcp-adapter-initializer' ),
				);
			}

			$parent_post = get_post( $global_styles_id );

			if ( ! $parent_post ) {
				return array(
					'success' => false,
					'message' => sprintf(
						__( 'Post "%d" not found', 'mcp-adapter-initializer' ),
						$global_styles_id
					),
				);
			}

			if ( 'wp_global_styles' !== $parent_post->post_type ) {
				return array(
					'success' => false,
					'message' => sprintf(
						__( 'Post "%1$d" is not a wp_global_styles post (got "%2$s")', 'mcp-adapter-initializer' ),
						$global_styles_id,
						$parent_post->post_type
					),
				);
			}

			$request = new \WP_REST_Request( 'GET', '/wp/v2/global-styles/' . $global_styles_id . '/revisions' );
			$request->set_param( 'parent', $global_styles_id );
			$request->set_param( 'per_page', isset( $input['per_page'] ) ? (int) $input['per_page'] : 10 );
			$request->set_param( 'page', isset( $input['page'] ) ? (int) $input['page'] : 1 );

			if ( ! class_exists( '\WP_REST_Global_Styles_Revisions_Controller' ) ) {
				return array(
					'success' => false,
					'message' => __( 'Global styles revisions are not supported on this WordPress version', 'mcp-adapter-initializer' ),
				);
			}

			$controller = new \WP_REST_Global_Styles_Revisions_Controller( 'wp_global_styles' );
			$response   = $controller->get_items( $request );

			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'message' => $response->get_error_message(),
				);
			}

			$revisions   = $response->get_data();
			$headers     = $response->get_headers();
			$total       = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $revisions );
			$total_pages = isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : 1;

			return array(
				'success'     => true,
				'revisions'   => $revisions,
				'total'       => $total,
				'total_pages' => $total_pages,
				'message'     => sprintf(
					__( 'Retrieved %1$d revision(s) for global styles post "%2$d"', 'mcp-adapter-initializer' ),
					count( $revisions ),
					$global_styles_id
				),
			);

		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Error listing global styles revisions: %s', 'mcp-adapter-initializer' ),
					$e->getMessage()
				),
			);
		}
	}
}
