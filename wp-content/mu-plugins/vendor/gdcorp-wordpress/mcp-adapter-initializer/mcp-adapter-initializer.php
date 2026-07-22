<?php
/**
 * MCP Adapter Initializer
 *
 * @package     mcp-adapter-initializer
 * @author      GoDaddy
 * @copyright   2025 GoDaddy
 * @license     GPL-2.0-or-later
 *
 * Plugin Name:       MCP Adapter Initializer
 * Plugin URI:        https://github.com/gdcorp-wordpress/mcp-adapter-initializer
 * Description:       Initialize a custom MCP server with custom tools and authentication.
 * Requires at least: 6.8
 * Version:           1.6.5
 * Requires PHP:      8.1
 * Author:            GoDaddy
 * Author URI:        https://www.godaddy.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       mcp-adapter-initializer
 */

// Prevent direct access.

use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Providers\JWT_Auth_Provider;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Providers\Signature_Auth_Provider;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Rest_Authenticator;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Dependencies\WP\MCP\Core\McpAdapter;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Dependencies\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Server\Stateless_JWT_Transport;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Activate_Plugin_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Activate_Theme_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Create_Navigation_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Create_Page_Draft_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Draft_Page_Helper;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Create_Post_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Deactivate_Plugin_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Delete_Media_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Delete_Navigation_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Delete_Page_Revision_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Delete_Post_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Delete_Template_Part_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Delete_Template_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Discard_Page_Draft_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_All_Media_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Block_Patterns_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Post_By_Option_Name_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Block_Types_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Global_Styles_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Media_By_Id_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Navigation_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Page_Draft_Status_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Page_Revision_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Plugin_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Post_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Get_Themes_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Global_Styles_Revisions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Global_Styles_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Media_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Navigation_Revisions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Navigations_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Page_Revisions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Plugins_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Post_Revisions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Posts_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Template_Part_Revisions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Template_Parts_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Template_Revisions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Templates_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Publish_Page_Draft_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Restore_Post_Revision_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Site_Info_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Switch_Theme_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Global_Styles_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Media_Meta_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Navigation_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Plugin_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Post_Image_Alt_Text_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Post_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Site_Options_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Template_Part_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Update_Template_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Upload_Image_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Cancel_Scheduled_Action_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\List_Scheduled_Actions_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Schedule_Action_Tool;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools\Schedule_Recurring_Action_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load should_run() scoping functions.
require_once __DIR__ . '/should_run.php';

// Early exit if plugin should not run in current context.
if ( ! gd_mcp_adapter_initializer_should_run() ) {
	return;
}

// Load production autoloader (Mozart-prefixed dependencies + plugin code).
// This is a Composer-generated autoloader located in dependencies/.
$gd_mcp_dependencies_autoloader = __DIR__ . '/dependencies/autoload.php';
if ( file_exists( $gd_mcp_dependencies_autoloader ) ) {
	require $gd_mcp_dependencies_autoloader;
}

// Load dev dependencies autoloader (only present in development environment).
// This includes PHPUnit, Mockery, code standards, etc.
$gd_mcp_vendor_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $gd_mcp_vendor_autoloader ) ) {
	require $gd_mcp_vendor_autoloader;
}

// Define plugin constants.
define( 'GD_MCP_ADAPTER_INITIALIZER_VERSION', '1.6.5' );
define( 'GD_MCP_ADAPTER_INITIALIZER_PLUGIN_FILE', __FILE__ );
define( 'GD_MCP_ADAPTER_INITIALIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GD_MCP_ADAPTER_INITIALIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for MCP Adapter Initializer
 */
class MCP_Adapter_Initializer {

	/**
	 * Plugin instance
	 *
	 * @var MCP_Adapter_Initializer|null
	 */
	private static ?MCP_Adapter_Initializer $instance = null;

	/**
	 * Server ID
	 *
	 * @var string
	 */
	private string $server_id = 'gd-mcp';

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private string $api_namespace = 'gd-mcp/v1';

	/**
	 * API route
	 *
	 * @var string
	 */
	private string $api_route = 'mcp/streamable';

	/**
	 * Plugin constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Get singleton instance
	 *
	 * @return MCP_Adapter_Initializer
	 */
	public static function get_instance(): MCP_Adapter_Initializer {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * MCP WooCommerce instance
	 *
	 * @var MCP_WooCommerce
	 */
	private MCP_WooCommerce $mcp_woo_commerce;

	/**
	 * Available tools
	 *
	 * @var array
	 */
	private array $tools = array();

	/**
	 * Ability categories
	 *
	 * @var array
	 */
	private array $ability_categories = array();

	/**
	 * Initialize the plugin
	 */
	private function init(): void {
		// Disable default MCP server - we create our own custom 'gd-mcp' server.
		// The default server tries to use built-in abilities that may not be registered
		// due to timing issues with multiple Abilities API versions.
		add_filter( 'mcp_adapter_create_default_server', '__return_false' );

		// Trigger MCP Adapter initialization.
		McpAdapter::instance();

		// WooCommerce MCP.
		$this->mcp_woo_commerce = MCP_WooCommerce::get_instance();

		// Initialize tools and categories.
		$this->init_tools();
		$this->init_ability_categories();

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		// Legacy compatibility.
		add_action( 'abilities_api_categories_init', array( $this, 'register_ability_categories' ) );
		add_action( 'abilities_api_init', array( $this, 'register_abilities' ) );

		add_action( 'mcp_adapter_init', array( $this, 'initialize_mcp_server' ) );

		add_filter( 'gdl_unrestricted_rest_endpoints', array( $this, 'add_unrestricted_endpoints' ) );

		// Draft page data integrity: clean up orphaned meta when posts are deleted.
		add_action( 'before_delete_post', array( Draft_Page_Helper::class, 'cleanup_draft_meta' ) );

		// REST endpoints for browser-based draft publish/discard (used by the WP admin banner).
		add_action( 'rest_api_init', array( Draft_Page_Helper::class, 'register_rest_routes' ) );

		// Fix GoDaddy system plugin block count issue with NULL block names.
		add_action( 'init', array( $this, 'fix_gd_block_count_null_issue' ), 1 );

		// Scheduling bridge: register the wp-extensions `mcp_tool_call` task so
		// scheduled MCP tool runs are dispatched here at AS fire time.
		add_action( 'init', array( $this, 'register_scheduling_bridge' ), 20 );

		$providers = apply_filters(
			'gd_mcp_auth_providers',
			array(
				new Signature_Auth_Provider(),
				new JWT_Auth_Provider(),
			)
		);

		$authenticator = Rest_Authenticator::init( $providers );

		// Priority 10 (default) — MCP auth is self-contained via can_handle()
		// so ordering with third-party auth plugins does not matter.
		add_filter( 'determine_current_user', array( $authenticator, 'determine_current_user' ), 10, 1 );
		add_filter( 'rest_authentication_errors', array( $authenticator, 'check_authentication_errors' ), 10, 1 );
	}

	/**
	 * Check if WordPress core has native Abilities API.
	 *
	 * WordPress 6.9+ has native Abilities API with category support.
	 * Pre-6.9 uses bundled Abilities API without categories.
	 *
	 * We detect which API is being used by checking if the bundled version was loaded.
	 * The bundled version defines WP_ABILITIES_API_VERSION constant.
	 *
	 * @return bool True if using WordPress 6.9+ native API, false if using bundled API.
	 */
	private function core_has_abilities_api(): bool {
		global $wp_version;

		// If WP_ABILITIES_API_VERSION is defined, we're using the bundled API (pre-6.9).
		if ( defined( 'WP_ABILITIES_API_VERSION' ) ) {
			return false;
		}

		// Validate that WordPress version is at least 6.9.
		return version_compare( $wp_version, '6.9', '>=' );
	}

	/**
	 * Initialize ability categories
	 */
	private function init_ability_categories(): void {
		$this->ability_categories['content-management'] = array(
			'label'       => __( 'Content Management', 'mcp-adapter-initializer' ),
			'description' => __( 'Abilities for managing and organizing content.', 'mcp-adapter-initializer' ),
		);

		$this->ability_categories['media-management'] = array(
			'label'       => __( 'Media Management', 'mcp-adapter-initializer' ),
			'description' => __( 'Abilities for managing media files and assets.', 'mcp-adapter-initializer' ),
		);

		$this->ability_categories['site-management'] = array(
			'label'       => __( 'Site Management', 'mcp-adapter-initializer' ),
			'description' => __( 'Abilities for managing site settings and configuration.', 'mcp-adapter-initializer' ),
		);

		$this->ability_categories['theme-management'] = array(
			'label'       => __( 'Theme Management', 'mcp-adapter-initializer' ),
			'description' => __( 'Abilities for managing themes and global styles.', 'mcp-adapter-initializer' ),
		);

		$this->ability_categories['plugin-management'] = array(
			'label'       => __( 'Plugin Management', 'mcp-adapter-initializer' ),
			'description' => __( 'Abilities for managing plugins.', 'mcp-adapter-initializer' ),
		);
	}

	/**
	 * Initialize tools
	 */
	private function init_tools(): void {
		$this->tools['site_info']                    = Site_Info_Tool::get_instance();
		$this->tools['get_post']                     = Get_Post_Tool::get_instance();
		$this->tools['update_post']                  = Update_Post_Tool::get_instance();
		$this->tools['update_post_image_alt_text']   = Update_Post_Image_Alt_Text_Tool::get_instance();
		$this->tools['create_post']                  = Create_Post_Tool::get_instance();
		$this->tools['upload_image']                 = Upload_Image_Tool::get_instance();
		$this->tools['update_site_options']          = Update_Site_Options_Tool::get_instance();
		$this->tools['get_post_by_option_name']      = Get_Post_By_Option_Name_Tool::get_instance();
		$this->tools['activate_plugin']              = Activate_Plugin_Tool::get_instance();
		$this->tools['deactivate_plugin']            = Deactivate_Plugin_Tool::get_instance();
		$this->tools['list_plugins']                 = List_Plugins_Tool::get_instance();
		$this->tools['get_plugin']                   = Get_Plugin_Tool::get_instance();
		$this->tools['update_plugin']                = Update_Plugin_Tool::get_instance();
		$this->tools['get_block_types']              = Get_Block_Types_Tool::get_instance();
		$this->tools['get_block_patterns']           = Get_Block_Patterns_Tool::get_instance();
		$this->tools['get_themes']                   = Get_Themes_Tool::get_instance();
		$this->tools['activate_theme']               = Activate_Theme_Tool::get_instance();
		$this->tools['switch_theme']                 = Switch_Theme_Tool::get_instance();
		$this->tools['get_global_styles']            = Get_Global_Styles_Tool::get_instance();
		$this->tools['list_global_styles']           = List_Global_Styles_Tool::get_instance();
		$this->tools['get_all_media']                = Get_All_Media_Tool::get_instance();
		$this->tools['list_media']                   = List_Media_Tool::get_instance();
		$this->tools['get_media_by_id']              = Get_Media_By_Id_Tool::get_instance();
		$this->tools['update_media_meta']            = Update_Media_Meta_Tool::get_instance();
		$this->tools['delete_media']                 = Delete_Media_Tool::get_instance();
		$this->tools['delete_post']                  = Delete_Post_Tool::get_instance();
		$this->tools['list_posts']                   = List_Posts_Tool::get_instance();
		$this->tools['list_post_revisions']          = List_Post_Revisions_Tool::get_instance();
		$this->tools['get_page_revision']            = Get_Page_Revision_Tool::get_instance();
		$this->tools['delete_page_revision']         = Delete_Page_Revision_Tool::get_instance();
		$this->tools['restore_post_revision']        = Restore_Post_Revision_Tool::get_instance();
		$this->tools['list_navigations']             = List_Navigations_Tool::get_instance();
		$this->tools['create_navigation']            = Create_Navigation_Tool::get_instance();
		$this->tools['get_navigation']               = Get_Navigation_Tool::get_instance();
		$this->tools['update_navigation']            = Update_Navigation_Tool::get_instance();
		$this->tools['delete_navigation']            = Delete_Navigation_Tool::get_instance();
		$this->tools['list_navigation_revisions']    = List_Navigation_Revisions_Tool::get_instance();
		$this->tools['update_template_part']         = Update_Template_Part_Tool::get_instance();
		$this->tools['update_global_styles']         = Update_Global_Styles_Tool::get_instance();
		$this->tools['update_template']              = Update_Template_Tool::get_instance();
		$this->tools['list_templates']               = List_Templates_Tool::get_instance();
		$this->tools['list_template_parts']          = List_Template_Parts_Tool::get_instance();
		$this->tools['delete_template']              = Delete_Template_Tool::get_instance();
		$this->tools['delete_template_part']         = Delete_Template_Part_Tool::get_instance();
		$this->tools['list_template_revisions']      = List_Template_Revisions_Tool::get_instance();
		$this->tools['list_template_part_revisions'] = List_Template_Part_Revisions_Tool::get_instance();
		$this->tools['list_global_styles_revisions'] = List_Global_Styles_Revisions_Tool::get_instance();

		// TODO: Remove once Site Designer API ships the list-post-revisions tool ID.
		// Legacy alias so callers still hitting `gd-mcp/list-page-revisions` keep working.
		// Kept outside the aligned block above so its explanatory comment does not
		// split PHPCS's alignment group.
		$this->tools['list_page_revisions']   = List_Page_Revisions_Tool::get_instance();
		$this->tools['create_page_draft']     = Create_Page_Draft_Tool::get_instance();
		$this->tools['get_page_draft_status'] = Get_Page_Draft_Status_Tool::get_instance();
		$this->tools['publish_page_draft']    = Publish_Page_Draft_Tool::get_instance();
		$this->tools['discard_page_draft']    = Discard_Page_Draft_Tool::get_instance();

		// Scheduling tools — registered LAST so their dynamic input_schemas can read
		// every other registered ability when build_input_schema() runs.
		$this->tools['schedule_action']           = Schedule_Action_Tool::get_instance();
		$this->tools['schedule_recurring_action'] = Schedule_Recurring_Action_Tool::get_instance();
		$this->tools['list_scheduled_actions']    = List_Scheduled_Actions_Tool::get_instance();
		$this->tools['cancel_scheduled_action']   = Cancel_Scheduled_Action_Tool::get_instance();
	}

	/**
	 * Register ability categories (WordPress 6.9+ only)
	 *
	 * This method only runs when WordPress 6.9+ native Abilities API is available.
	 * The bundled API (pre-6.9) does not support categories.
	 */
	public function register_ability_categories(): void {
		// Only register categories if the function exists (WordPress 6.9+).
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		// Register all categories.
		foreach ( $this->ability_categories as $category => $args ) {
			wp_register_ability_category( $category, $args );
		}
	}

	/**
	 * Register plugin abilities
	 */
	public function register_abilities(): void {
		// Register all tools.
		foreach ( $this->tools as $tool ) {
			if ( method_exists( $tool, 'register' ) ) {
				$tool->register();
			}
		}
		// Register woo tools.
		$this->mcp_woo_commerce->register_abilities();
	}

	/**
	 * Initialize MCP server
	 *
	 * @param McpAdapter $adapter MCP adapter instance.
	 */
	public function initialize_mcp_server( $adapter ): void {
		// Check if server already exists to prevent duplicate registration.
		if ( $adapter->get_server( $this->server_id ) ) {
			return;
		}

		$this->mcp_woo_commerce->disable_validation();

		try {
			$adapter->create_server(
				$this->server_id,
				$this->api_namespace,
				$this->api_route,
				__( 'MCP Server', 'mcp-adapter-initializer' ),
				__( 'An MCP server for executing tools.', 'mcp-adapter-initializer' ),
				GD_MCP_ADAPTER_INITIALIZER_VERSION,
				$this->get_transport_methods(),
				$this->get_error_handler(),
				null,
				$this->get_exposed_abilities(),
				array(), // Resources.
				array(), // Prompts.
				null
			);
		} catch ( Exception $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error initializing MCP server: ' . $exception->getMessage() );
			}
		}

		// Re-enable MCP validation immediately after server creation.
		$this->mcp_woo_commerce->enable_validation();
	}

	/**
	 * Add unrestricted endpoints for MCP
	 *
	 * @param array $endpoints Existing unrestricted endpoints.
	 *
	 * @return array Modified endpoints
	 */
	public function add_unrestricted_endpoints( array $endpoints ): array {
		$endpoints[] = '/gd-mcp/v1';

		return $endpoints;
	}

	/**
	 * Fix GoDaddy system plugin block count issue with NULL block names
	 *
	 * The GoDaddy system plugin's Block_Count class calls array_count_values()
	 * on an array that can contain NULL values (from non-block content in parsed blocks).
	 * This causes a PHP warning. We hook in early to add a custom error handler
	 * that suppresses this specific warning.
	 *
	 * @return void
	 */
	public function fix_gd_block_count_null_issue(): void {
		// Only fix the issue if the GoDaddy system plugin is active.
		if ( ! class_exists( 'WPaaS\Admin\Block_Count' ) ) {
			return;
		}

		// Add custom error handler hooks at the right priorities.
		add_action( 'save_post', array( $this, 'setup_block_count_error_handler' ), 1, 3 );
		add_action( 'save_post', array( $this, 'restore_block_count_error_handler' ), 999, 0 );
	}

	/**
	 * Set up custom error handler for block count warnings
	 *
	 * This runs early in the save_post hook (priority 1) to set up
	 * a custom error handler that suppresses the specific array_count_values warning.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $_post   Post object.
	 * @param bool     $_update Whether this is an update.
	 * @return void
	 *
	 * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by save_post hook signature.
	 */
	public function setup_block_count_error_handler( $post_id, $_post, $_update ): void {
		// Set up error handler to suppress the specific warning.
		// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		set_error_handler(
			function ( $errno, $errstr, $errfile, $_errline ) use ( $post_id ) {
				// Only suppress the specific array_count_values warning in the GoDaddy plugin.
				if ( E_WARNING === $errno
					&& false !== strpos( $errstr, 'array_count_values()' )
					&& false !== strpos( $errfile, 'gd-system-plugin/includes/admin/class-block-count.php' )
				) {
					// Log the suppressed warning for debugging purposes (optional).
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
						error_log(
							sprintf(
								'MCP Adapter: Suppressed array_count_values warning in GoDaddy Block_Count (Post ID: %d). This is expected for posts with block content.',
								$post_id
							)
						);
					}
					return true; // Suppress the warning.
				}

				// Let other errors pass through to the default handler.
				return false;
			},
			E_WARNING
		);
		// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	}

	/**
	 * Restore the previous error handler
	 *
	 * This runs late in the save_post hook (priority 999) to restore
	 * the previous error handler after the GoDaddy plugin has finished.
	 *
	 * @return void
	 */
	public function restore_block_count_error_handler(): void {
		restore_error_handler();
	}

	/**
	 * Get transport methods
	 *
	 * @return array
	 */
	private function get_transport_methods(): array {
		return array(
			Stateless_JWT_Transport::class,
		);
	}

	/**
	 * Get error handler class
	 *
	 * @return string
	 */
	private function get_error_handler(): string {
		return ErrorLogMcpErrorHandler::class;
	}

	/**
	 * Get abilities to expose as tools
	 *
	 * @return array
	 */
	private function get_exposed_abilities(): array {
		$abilities = array();

		// Get tool IDs from all registered tools.
		foreach ( $this->tools as $tool ) {
			if ( method_exists( $tool, 'get_tool_id' ) ) {
				$abilities[] = $tool->get_tool_id();
			}
		}

		// Woo abilities.
		$woo_abilities = $this->mcp_woo_commerce->get_exposed_abilities();

		return array_merge( $abilities, $woo_abilities );
	}

	/**
	 * Register the wp-extensions `mcp_tool_call` task so scheduled MCP tool
	 * runs are dispatched back here when Action Scheduler fires.
	 *
	 * No-op if wp-extensions isn't loaded (the global API function is missing).
	 */
	public function register_scheduling_bridge(): void {
		if ( ! function_exists( 'wp_sd_register_task' ) ) {
			return;
		}

		wp_sd_register_task(
			'mcp_tool_call',
			array( $this, 'run_scheduled_mcp_tool' ),
			array(),
			array(
				'label'       => __( 'Run any registered MCP tool', 'mcp-adapter-initializer' ),
				'description' => __( 'Dispatcher task — wp-extensions fires this hook with payload { tool_id, input }; the MCP plugin looks the tool up and calls execute_with_admin().', 'mcp-adapter-initializer' ),
			)
		);
	}

	/**
	 * AS-fired handler: dispatches a scheduled MCP tool execution.
	 *
	 * Receives the payload that was passed to wp_sd_schedule(). Reserved
	 * underscore-prefixed keys (audit metadata) are stripped by the
	 * wp-extensions runner before the payload reaches us.
	 *
	 * @param array $payload Single positional payload arg.
	 */
	public function run_scheduled_mcp_tool( array $payload ): void {
		$tool_id = isset( $payload['tool_id'] ) ? (string) $payload['tool_id'] : '';
		$input   = isset( $payload['input'] ) && is_array( $payload['input'] ) ? $payload['input'] : array();

		if ( '' === $tool_id ) {
			error_log( '[gd-mcp] scheduled tool dispatch: empty tool_id — skipping' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		if ( ! function_exists( 'wp_get_ability' ) ) {
			error_log( '[gd-mcp] scheduled tool dispatch: abilities API not loaded — skipping' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$ability = wp_get_ability( $tool_id );
		if ( ! $ability ) {
			error_log( sprintf( '[gd-mcp] scheduled tool "%s" not found in abilities registry — skipping', $tool_id ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		// Ability::execute() runs input/output validation + the registered
		// permission_callback, then invokes the registered execute_callback
		// (which for our tools is Base_Tool::execute_with_admin).
		$ability->execute( $input );
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {
	}

	/**
	 * Prevent unserialization
	 *
	 * @throws Exception When attempting to unserialize the singleton.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}

// Initialize the plugin.
MCP_Adapter_Initializer::get_instance();
