<?php
/**
 * Get Plugin Tool Class
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
 * Get Plugin Tool
 *
 * Handles the registration and execution of the get plugin ability
 * for the MCP adapter. Provides functionality similar to the WordPress
 * REST API /wp/v2/plugins/<plugin> endpoint, extended with update
 * availability fields (update_available, new_version) sourced from the
 * update_plugins site transient — equivalent to what `wp plugin list`
 * surfaces but which core's REST controller does not expose.
 */
class Get_Plugin_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/get-plugin';

	/**
	 * Tool instance
	 *
	 * @var Get_Plugin_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Get_Plugin_Tool
	 */
	public static function get_instance(): Get_Plugin_Tool {
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
	 * Register the get plugin ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Get Plugin', 'mcp-adapter-initializer' ),
				'description'         => __( 'Retrieves information about a specific WordPress plugin by its slug, including current version and available-update info (whether an update exists and the target version)', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'plugin-management',
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
				'plugin_slug' => array(
					'type'        => 'string',
					'description' => __( 'The plugin slug (e.g., "hello-dolly", "akismet")', 'mcp-adapter-initializer' ),
					'minLength'   => 1,
				),
				'context'     => array(
					'type'        => 'string',
					'description' => __( 'Scope under which the request is made; determines fields present in response', 'mcp-adapter-initializer' ),
					'enum'        => array( 'view', 'embed', 'edit' ),
					'default'     => 'view',
				),
				'force_check' => array(
					'type'        => 'boolean',
					'description' => __( 'Force a refresh of available plugin updates from WordPress.org before reading. Defaults to false; the cached transient is normally kept fresh by core cron.', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
			),
			'required'   => array( 'plugin_slug' ),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Plugin retrieval result', 'mcp-adapter-initializer' ),
			array(
				'plugin'           => array(
					'type'        => 'string',
					'description' => __( 'The plugin file', 'mcp-adapter-initializer' ),
				),
				'status'           => array(
					'type'        => 'string',
					'description' => __( 'The plugin activation status', 'mcp-adapter-initializer' ),
				),
				'name'             => array(
					'type'        => 'string',
					'description' => __( 'The plugin name', 'mcp-adapter-initializer' ),
				),
				'plugin_uri'       => array(
					'type'        => 'string',
					'description' => __( 'The plugin\'s website address', 'mcp-adapter-initializer' ),
				),
				'author'           => array(
					'type'        => 'string',
					'description' => __( 'The plugin author', 'mcp-adapter-initializer' ),
				),
				'author_uri'       => array(
					'type'        => 'string',
					'description' => __( 'Plugin author\'s website address', 'mcp-adapter-initializer' ),
				),
				'description'      => array(
					'type'        => 'string',
					'description' => __( 'The plugin description', 'mcp-adapter-initializer' ),
				),
				'version'          => array(
					'type'        => 'string',
					'description' => __( 'The plugin version number', 'mcp-adapter-initializer' ),
				),
				'network_only'     => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the plugin can only be activated network-wide', 'mcp-adapter-initializer' ),
				),
				'requires_wp'      => array(
					'type'        => 'string',
					'description' => __( 'Minimum required version of WordPress', 'mcp-adapter-initializer' ),
				),
				'requires_php'     => array(
					'type'        => 'string',
					'description' => __( 'Minimum required version of PHP', 'mcp-adapter-initializer' ),
				),
				'textdomain'       => array(
					'type'        => 'string',
					'description' => __( 'The plugin\'s text domain', 'mcp-adapter-initializer' ),
				),
				'update_available' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether a newer version is available for this plugin', 'mcp-adapter-initializer' ),
				),
				'new_version'      => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The version that is available when update_available is true; null otherwise', 'mcp-adapter-initializer' ),
				),
			)
		);
	}

	/**
	 * Execute the get plugin tool
	 *
	 * @param array $input Input parameters
	 * @return array Plugin information
	 */
	public function execute( array $input ): array {
		// Validate input
		if ( empty( $input['plugin_slug'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Plugin slug is required', 'mcp-adapter-initializer' ),
			);
		}

		$plugin_slug = sanitize_text_field( $input['plugin_slug'] );

		// Check if user has permission to view plugins
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to view plugins', 'mcp-adapter-initializer' ),
			);
		}

		$plugin_file = Plugin_Helper::find_file( $plugin_slug );

		if ( ! $plugin_file ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Plugin "%s" is not installed', 'mcp-adapter-initializer' ), $plugin_slug ),
			);
		}

		// Get plugin data
		if ( ! function_exists( 'get_plugin_data' ) ) {
			$this->load_admin_file( 'plugin.php' );
		}

		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Determine if plugin is active
		$active_plugins = get_option( 'active_plugins', array() );
		$is_active      = in_array( $plugin_file, $active_plugins, true );
		$status         = $is_active ? 'active' : 'inactive';

		Plugin_Helper::refresh_updates( ! empty( $input['force_check'] ) );

		// Build plugin information
		$plugin_info = array(
			'success'      => true,
			'plugin'       => $plugin_file,
			'status'       => $status,
			'name'         => $plugin_data['Name'],
			'plugin_uri'   => $plugin_data['PluginURI'],
			'author'       => $plugin_data['AuthorName'],
			'author_uri'   => $plugin_data['AuthorURI'],
			'description'  => $plugin_data['Description'],
			'version'      => $plugin_data['Version'],
			'network_only' => $plugin_data['Network'],
			'requires_wp'  => $plugin_data['RequiresWP'],
			'requires_php' => $plugin_data['RequiresPHP'],
			'textdomain'   => $plugin_data['TextDomain'],
		);

		$plugin_info = array_merge( $plugin_info, Plugin_Helper::get_update_info( $plugin_file ) );

		// Apply context filtering
		$context = ! empty( $input['context'] ) ? $input['context'] : 'view';
		if ( 'embed' === $context ) {
			// For embed context, return minimal data
			$plugin_info = array(
				'success' => true,
				'plugin'  => $plugin_info['plugin'],
				'status'  => $plugin_info['status'],
				'name'    => $plugin_info['name'],
			);
		}

		return $plugin_info;
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
