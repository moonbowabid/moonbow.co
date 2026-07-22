<?php
/**
 * Get post by WordPress option name (post ID stored in options).
 *
 * @package   mcp-adapter-initializer
 * @author    GoDaddy
 * @copyright Copyright (c) GoDaddy.com, LLC
 * @license   GPL-2.0-or-later
 */

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves a post ID from a site option and returns the same payload as {@see Get_Post_Tool}
 * in one step (no separate get-post round trip from the client).
 */
class Get_Post_By_Option_Name_Tool extends Base_Tool {

	/**
	 * Tool identifier (MCP tool name: gd-mcp-get-post-by-option-name)
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/get-post-by-option-name';

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Get post by option name', 'mcp-adapter-initializer' ),
				'description'         => __( 'Reads a site option that stores a post ID, then returns that post (page, post, or custom post type) using the same shape as Get Post (single round trip).', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'content-management',
			)
		);
	}

	public function get_tool_id(): string {
		return self::TOOL_ID;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'option_name'  => array(
					'type'        => 'string',
					'description' => __( 'Option name whose value is a post ID', 'mcp-adapter-initializer' ),
				),
				'include_meta' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include post meta (same as get-post).', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
			),
			'required'   => array( 'option_name' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_output_schema(): array {
		return Get_Post_Tool::get_instance()->get_output_schema();
	}

	/**
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>
	 */
	public function execute( array $input ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return Get_Post_Tool::get_instance()->execute( array( 'post_id' => 0 ) );
		}

		$option_name = isset( $input['option_name'] ) ? sanitize_key( (string) $input['option_name'] ) : '';
		if ( '' === $option_name ) {
			return Get_Post_Tool::get_instance()->execute( array( 'post_id' => 0 ) );
		}

		$raw     = get_option( $option_name, null );
		$post_id = 0;
		if ( null !== $raw && '' !== $raw ) {
			$n       = is_numeric( $raw ) ? (int) $raw : 0;
			$post_id = $n > 0 ? $n : 0;
		}

		return Get_Post_Tool::get_instance()->execute(
			array(
				'post_id'      => $post_id,
				'include_meta' => ! empty( $input['include_meta'] ),
			)
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
