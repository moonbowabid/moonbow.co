<?php
/**
 * Update Plugin Tool Class
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
 * Update Plugin Tool
 *
 * Handles the registration and execution of the update plugin ability
 * for the MCP adapter. Updates an installed plugin from the WordPress.org
 * repository. If a specific version is provided, installs that exact version
 * (supports upgrade or downgrade); otherwise updates to the latest available
 * version.
 */
class Update_Plugin_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/update-plugin';

	/**
	 * Tool instance
	 *
	 * @var Update_Plugin_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Update_Plugin_Tool
	 */
	public static function get_instance(): Update_Plugin_Tool {
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
	 * Register the update plugin ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Update Plugin', 'mcp-adapter-initializer' ),
				'description'         => __( 'Updates an installed WordPress plugin from the WordPress.org repository. If "version" is provided, installs that exact version (allows upgrade or downgrade); otherwise updates to the latest available version.', 'mcp-adapter-initializer' ),
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
				'version'     => array(
					'type'        => 'string',
					'description' => __( 'Optional target version (e.g., "1.7.2"). If omitted, updates to the latest available version. Supplying a version supports both upgrade and downgrade.', 'mcp-adapter-initializer' ),
					'minLength'   => 1,
				),
				'force_check' => array(
					'type'        => 'boolean',
					'description' => __( 'When "version" is omitted, force a refresh of available plugin updates from WordPress.org before resolving the latest version. Ignored when "version" is supplied. Defaults to true so that "update to latest" reflects the freshest data available.', 'mcp-adapter-initializer' ),
					'default'     => true,
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
			__( 'Plugin update result', 'mcp-adapter-initializer' ),
			array(
				'plugin'           => array(
					'type'        => 'string',
					'description' => __( 'The plugin slug that was processed', 'mcp-adapter-initializer' ),
				),
				'previous_version' => array(
					'type'        => 'string',
					'description' => __( 'The plugin version before the update', 'mcp-adapter-initializer' ),
				),
				'version'          => array(
					'type'        => 'string',
					'description' => __( 'The plugin version after the update', 'mcp-adapter-initializer' ),
				),
			)
		);
	}

	/**
	 * Execute the update plugin tool
	 *
	 * @param array $input Input parameters
	 * @return array Plugin update result
	 */
	public function execute( array $input ): array {
		// Validate input
		if ( empty( $input['plugin_slug'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Plugin slug is required', 'mcp-adapter-initializer' ),
				'plugin'  => '',
			);
		}

		$plugin_slug    = sanitize_text_field( $input['plugin_slug'] );
		$target_version = ! empty( $input['version'] ) ? sanitize_text_field( $input['version'] ) : null;

		// Check if user has permission to update plugins
		if ( ! current_user_can( 'update_plugins' ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to update plugins', 'mcp-adapter-initializer' ),
				'plugin'  => $plugin_slug,
			);
		}

		// Load wp-admin/includes/plugin.php unconditionally: this tool calls
		// is_plugin_active(), deactivate_plugins(), activate_plugin(), and get_plugins(),
		// all of which live in that file. load_admin_file() wraps require_once so the
		// call is idempotent and cheap.
		$this->load_admin_file( 'plugin.php' );

		if ( ! function_exists( 'plugins_api' ) ) {
			$this->load_admin_file( 'plugin-install.php' );
		}

		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			$this->load_admin_file( 'file.php' );
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			$this->load_admin_file( 'class-wp-upgrader.php' );
		}

		$plugin_file = Plugin_Helper::find_file( $plugin_slug );
		if ( ! $plugin_file ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Plugin "%s" is not installed', 'mcp-adapter-initializer' ), $plugin_slug ),
				'plugin'  => $plugin_slug,
			);
		}

		$previous_version = $this->get_plugin_version( $plugin_file );

		// Resolve the target version up front. When not supplied, look up the
		// latest available version from the update_plugins transient. Unlike
		// list-plugins / get-plugin, force_check defaults to true here: this
		// is a write tool whose semantics are "install the latest version
		// right now," so a stale transient could install a version that has
		// already been superseded. Callers that want the cached-only path
		// can pass force_check=false explicitly.
		if ( null === $target_version ) {
			$force_check = ! isset( $input['force_check'] ) || ! empty( $input['force_check'] );
			Plugin_Helper::refresh_updates( $force_check );

			$update_info = Plugin_Helper::get_update_info( $plugin_file );
			if ( ! $update_info['update_available'] || empty( $update_info['new_version'] ) ) {
				return array(
					'success'          => true,
					'message'          => __( 'Plugin is already up to date', 'mcp-adapter-initializer' ),
					'plugin'           => $plugin_slug,
					'previous_version' => $previous_version,
					'version'          => $previous_version,
				);
			}

			$target_version = $update_info['new_version'];
		}

		return $this->perform_update( $plugin_slug, $plugin_file, $previous_version, $target_version );
	}

	/**
	 * Install a specific version of a plugin from the WordPress.org repository.
	 *
	 * Supports both upgrade and downgrade by overwriting the existing install
	 * with the version-pinned package from the .org repo. Preserves activation
	 * state across the swap — if the install fails after deactivation, the
	 * plugin is reactivated before the error response is returned so it is
	 * never silently left disabled.
	 *
	 * @param string $plugin_slug      Plugin slug.
	 * @param string $plugin_file      Plugin file path relative to WP_PLUGIN_DIR.
	 * @param string $previous_version Plugin version before the update.
	 * @param string $target_version   Concrete target version to install.
	 * @return array
	 */
	private function perform_update( string $plugin_slug, string $plugin_file, string $previous_version, string $target_version ): array {
		if ( $target_version === $previous_version ) {
			return array(
				'success'          => true,
				'message'          => sprintf( __( 'Plugin is already at version %s', 'mcp-adapter-initializer' ), $target_version ),
				'plugin'           => $plugin_slug,
				'previous_version' => $previous_version,
				'version'          => $previous_version,
			);
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array( 'versions' => true ),
			)
		);

		if ( is_wp_error( $api ) ) {
			return array(
				'success'          => false,
				'message'          => sprintf( __( 'Plugin "%1$s" not found on WordPress.org: %2$s', 'mcp-adapter-initializer' ), $plugin_slug, $api->get_error_message() ),
				'plugin'           => $plugin_slug,
				'previous_version' => $previous_version,
			);
		}

		if ( empty( $api->versions ) || ! isset( $api->versions[ $target_version ] ) ) {
			return array(
				'success'          => false,
				'message'          => sprintf( __( 'Version "%1$s" is not available on WordPress.org for plugin "%2$s"', 'mcp-adapter-initializer' ), $target_version, $plugin_slug ),
				'plugin'           => $plugin_slug,
				'previous_version' => $previous_version,
			);
		}

		$download_url = $api->versions[ $target_version ];
		$was_active   = is_plugin_active( $plugin_file );

		// Deactivate before swapping files to avoid running mid-upgrade code.
		if ( $was_active ) {
			deactivate_plugins( $plugin_file, true );
		}

		$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$result   = $upgrader->install( $download_url, array( 'overwrite_package' => true ) );

		if ( is_wp_error( $result ) ) {
			return $this->install_failure_response(
				sprintf( __( 'Failed to install plugin version %1$s: %2$s', 'mcp-adapter-initializer' ), $target_version, $result->get_error_message() ),
				$plugin_slug,
				$plugin_file,
				$previous_version,
				$was_active
			);
		}

		if ( ! $result ) {
			return $this->install_failure_response(
				sprintf( __( 'Plugin install of version %s failed for unknown reason', 'mcp-adapter-initializer' ), $target_version ),
				$plugin_slug,
				$plugin_file,
				$previous_version,
				$was_active
			);
		}

		// Reactivate if it was active beforehand. Activation failure is reported
		// in the message but does not flip success — the install itself worked.
		$activation_message = '';
		if ( $was_active ) {
			$activation_result = activate_plugin( $plugin_file );
			if ( is_wp_error( $activation_result ) ) {
				$activation_message = sprintf( __( ' Plugin installed but failed to reactivate: %s', 'mcp-adapter-initializer' ), $activation_result->get_error_message() );
			}
		}

		$new_version = $this->get_plugin_version( $plugin_file );

		return array(
			'success'          => true,
			'message'          => sprintf( __( 'Plugin updated to version %s', 'mcp-adapter-initializer' ), $new_version ) . $activation_message,
			'plugin'           => $plugin_slug,
			'previous_version' => $previous_version,
			'version'          => $new_version,
		);
	}

	/**
	 * Build a failure response after an install attempt, restoring activation
	 * state first so a previously-active plugin is never silently left disabled
	 * after a failed install.
	 *
	 * @param string $message          Failure message for the response.
	 * @param string $plugin_slug      Plugin slug.
	 * @param string $plugin_file      Plugin file path relative to WP_PLUGIN_DIR.
	 * @param string $previous_version Plugin version before the update.
	 * @param bool   $was_active       Whether the plugin was active before the deactivate call.
	 * @return array
	 */
	private function install_failure_response( string $message, string $plugin_slug, string $plugin_file, string $previous_version, bool $was_active ): array {
		if ( $was_active ) {
			$activation_result = activate_plugin( $plugin_file );
			if ( is_wp_error( $activation_result ) ) {
				$message .= sprintf( __( ' Additionally, the plugin could not be reactivated and is now disabled: %s', 'mcp-adapter-initializer' ), $activation_result->get_error_message() );
			}
		}

		return array(
			'success'          => false,
			'message'          => $message,
			'plugin'           => $plugin_slug,
			'previous_version' => $previous_version,
		);
	}

	/**
	 * Get plugin version
	 *
	 * @param string $plugin_file Plugin file path
	 * @return string Plugin version
	 */
	private function get_plugin_version( string $plugin_file ): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			$this->load_admin_file( 'plugin.php' );
		}

		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
		return $plugin_data['Version'] ?? '';
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
