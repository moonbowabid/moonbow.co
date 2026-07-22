<?php
/**
 * Get Block Types Tool Class
 *
 * @package     mcp-adapter-initializer
 * @author      GoDaddy
 * @copyright   2025 GoDaddy
 * @license     GPL-2.0-or-later
 */

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get Block Types Tool
 *
 * Handles the registration and execution of the get block types ability
 * for the MCP adapter.
 */
class Get_Block_Types_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/get-block-types';

	/**
	 * Tool instance
	 *
	 * @var Get_Block_Types_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Get_Block_Types_Tool
	 */
	public static function get_instance(): Get_Block_Types_Tool {
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
	 * Register the get block types ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Get Block Types', 'mcp-adapter-initializer' ),
				'description'         => __( 'Retrieves all registered block types on the site', 'mcp-adapter-initializer' ),
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
			'properties' => array(),
			'required'   => array(),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Block types retrieval result', 'mcp-adapter-initializer' ),
			array(
				'block_types' => array(
					'type'        => 'array',
					'description' => __( 'Array of registered block types', 'mcp-adapter-initializer' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'           => array(
								'type'        => 'string',
								'description' => __( 'The block type name (e.g. core/paragraph)', 'mcp-adapter-initializer' ),
							),
							'title'          => array(
								'type'        => 'string',
								'description' => __( 'The block type title', 'mcp-adapter-initializer' ),
							),
							'description'    => array(
								'type'        => 'string',
								'description' => __( 'The block type description', 'mcp-adapter-initializer' ),
							),
							'category'       => array(
								'type'        => 'string',
								'description' => __( 'The block category', 'mcp-adapter-initializer' ),
							),
							'icon'           => array(
								'type'        => array( 'string', 'object' ),
								'description' => __( 'The block icon (dashicon slug or SVG object)', 'mcp-adapter-initializer' ),
							),
							'keywords'       => array(
								'type'        => 'array',
								'description' => __( 'Search keywords for block discovery', 'mcp-adapter-initializer' ),
								'items'       => array(
									'type' => 'string',
								),
							),
							'api_version'    => array(
								'type'        => 'integer',
								'description' => __( 'Block API version', 'mcp-adapter-initializer' ),
							),
							'is_dynamic'     => array(
								'type'        => 'boolean',
								'description' => __( 'Whether the block has a server-side render callback', 'mcp-adapter-initializer' ),
							),
							'parent'         => array(
								'type'        => 'array',
								'description' => __( 'Parent block types that can contain this block', 'mcp-adapter-initializer' ),
								'items'       => array(
									'type' => 'string',
								),
							),
							'ancestor'       => array(
								'type'        => 'array',
								'description' => __( 'Ancestor block types (any level) that must contain this block', 'mcp-adapter-initializer' ),
								'items'       => array(
									'type' => 'string',
								),
							),
							'allowed_blocks' => array(
								'type'        => 'array',
								'description' => __( 'Block types allowed as direct children', 'mcp-adapter-initializer' ),
								'items'       => array(
									'type' => 'string',
								),
							),
							'attributes'     => array(
								'type'        => 'object',
								'description' => __( 'Block attribute definitions (name → schema)', 'mcp-adapter-initializer' ),
							),
							'supports'       => array(
								'type'        => 'object',
								'description' => __( 'Block support settings (color, spacing, typography, etc.)', 'mcp-adapter-initializer' ),
							),
							'styles'         => array(
								'type'        => 'array',
								'description' => __( 'Registered block style variations', 'mcp-adapter-initializer' ),
								'items'       => array(
									'type' => 'object',
								),
							),
							'variations'     => array(
								'type'        => 'array',
								'description' => __( 'Block variations with preset configurations', 'mcp-adapter-initializer' ),
								'items'       => array(
									'type' => 'object',
								),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Execute the get block types tool
	 *
	 * @param array $input Input parameters (none required)
	 * @return array Block types result or error
	 */
	public function execute( array $input ): array {
		// Check if WP_Block_Type_Registry is available
		if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Block type registry is not available', 'mcp-adapter-initializer' ),
			);
		}

		// Get the block type registry
		$block_registry    = ( new \WP_Block_Type_Registry() )::get_instance();
		$registered_blocks = $block_registry->get_all_registered();

		if ( ! is_array( $registered_blocks ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to retrieve registered block types', 'mcp-adapter-initializer' ),
			);
		}

		$block_types = array();

		foreach ( $registered_blocks as $block_name => $block_type ) {
			$block_data = array(
				'name'           => $block_name,
				'title'          => isset( $block_type->title ) ? $block_type->title : '',
				'description'    => isset( $block_type->description ) ? $block_type->description : '',
				'category'       => isset( $block_type->category ) ? $block_type->category : '',
				'icon'           => isset( $block_type->icon ) ? $block_type->icon : '',
				'keywords'       => isset( $block_type->keywords ) ? $block_type->keywords : array(),
				'api_version'    => isset( $block_type->api_version ) ? (int) $block_type->api_version : 1,
				'is_dynamic'     => $block_type->is_dynamic(),
				'parent'         => isset( $block_type->parent ) ? $block_type->parent : array(),
				'ancestor'       => isset( $block_type->ancestor ) ? $block_type->ancestor : array(),
				'allowed_blocks' => isset( $block_type->allowed_blocks ) ? $block_type->allowed_blocks : array(),
				'attributes'     => $block_type->get_attributes(),
				'supports'       => isset( $block_type->supports ) ? $block_type->supports : array(),
				'styles'         => isset( $block_type->styles ) ? $block_type->styles : array(),
				'variations'     => isset( $block_type->variations ) ? $block_type->variations : array(),
			);

			$block_types[] = $block_data;
		}

		return array(
			'success'     => true,
			'block_types' => $block_types,
			// translators: %d is the number of block types found
			'message'     => sprintf( __( 'Retrieved %d registered block types', 'mcp-adapter-initializer' ), count( $block_types ) ),
		);
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
