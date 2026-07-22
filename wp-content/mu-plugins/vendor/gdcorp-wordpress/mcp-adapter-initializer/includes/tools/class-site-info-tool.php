<?php
/**
 * Site Info Tool Class
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
 * Site Information Tool
 *
 * Handles the registration and execution of the site information ability
 * for the MCP adapter.
 */
class Site_Info_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/get-site-info';

	/**
	 * Tool instance
	 *
	 * @var Site_Info_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Site_Info_Tool
	 */
	public static function get_instance(): Site_Info_Tool {
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
	 * Register the site information ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Get Site Information', 'mcp-adapter-initializer' ),
				'description'         => __( 'Retrieves basic information about the current WordPress site', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'site-management',
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
				'include_stats'            => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include post/page statistics', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
				'include_theme_info'       => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include active theme information', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
				'include_plugin_count'     => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include plugin count information', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
				'include_reading_settings' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include reading settings (front page display, blog page, etc.)', 'mcp-adapter-initializer' ),
					'default'     => false,
				),
			),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'site_name'         => array(
					'type'        => 'string',
					'description' => __( 'The name of the WordPress site', 'mcp-adapter-initializer' ),
				),
				'site_url'          => array(
					'type'        => 'string',
					'description' => __( 'The URL of the WordPress site', 'mcp-adapter-initializer' ),
				),
				'description'       => array(
					'type'        => 'string',
					'description' => __( 'The site tagline/description', 'mcp-adapter-initializer' ),
				),
				'wordpress_version' => array(
					'type'        => 'string',
					'description' => __( 'WordPress version', 'mcp-adapter-initializer' ),
				),
				'stats'             => array(
					'type'       => 'object',
					'properties' => array(
						'post_count' => array(
							'type'        => 'integer',
							'description' => __( 'Number of published posts', 'mcp-adapter-initializer' ),
						),
						'page_count' => array(
							'type'        => 'integer',
							'description' => __( 'Number of published pages', 'mcp-adapter-initializer' ),
						),
					),
				),
				'theme_info'        => array(
					'type'       => 'object',
					'properties' => array(
						'name'    => array( 'type' => 'string' ),
						'version' => array( 'type' => 'string' ),
						'author'  => array( 'type' => 'string' ),
					),
				),
				'plugin_count'      => array(
					'type'        => 'integer',
					'description' => __( 'Number of active plugins', 'mcp-adapter-initializer' ),
				),
				'reading_settings'  => array(
					'type'       => 'object',
					'properties' => array(
						'show_on_front'  => array(
							'type'        => 'string',
							'enum'        => array( 'posts', 'page' ),
							'description' => __( 'What the front page displays: "posts" or "page"', 'mcp-adapter-initializer' ),
						),
						'page_on_front'  => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Page ID used as the static front page, or null when not configured', 'mcp-adapter-initializer' ),
						),
						'page_for_posts' => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Page ID designated for blog posts, or null when not configured', 'mcp-adapter-initializer' ),
						),
					),
				),
				'is_published'      => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the site has been published (gdl_site_published option is set)', 'mcp-adapter-initializer' ),
				),
				'site_locale'       => array(
					'type'        => 'string',
					'description' => __( 'Active site locale from get_locale() (e.g. "es_ES", "en_US")', 'mcp-adapter-initializer' ),
				),
				'timezone'          => array(
					'type'        => 'string',
					'description' => __( 'Timezone — named zone (e.g. "America/New_York") or "+HH:MM"/"-HH:MM" offset for sites configured with a UTC offset', 'mcp-adapter-initializer' ),
				),
				'date_format'       => array(
					'type'        => 'string',
					'description' => __( 'PHP date format string', 'mcp-adapter-initializer' ),
				),
				'time_format'       => array(
					'type'        => 'string',
					'description' => __( 'PHP time format string', 'mcp-adapter-initializer' ),
				),
				'posts_per_page'    => array(
					'type'        => 'integer',
					'description' => __( 'Number of posts to show per page', 'mcp-adapter-initializer' ),
				),
				'blog_public'       => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the site is visible to search engines', 'mcp-adapter-initializer' ),
				),
				'site_logo'         => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'Media attachment ID for site logo, or null if not set', 'mcp-adapter-initializer' ),
				),
				'site_icon'         => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'Media attachment ID for site icon (favicon), or null if not set', 'mcp-adapter-initializer' ),
				),
			),
		);
	}

	/**
	 * Execute the site info tool
	 *
	 * @param array $input Input parameters
	 * @return array Site information
	 */
	public function execute( array $input ): array {
		$site_logo = (int) get_option( 'site_logo', 0 );
		$site_icon = (int) get_option( 'site_icon', 0 );

		$result = array(
			'site_name'         => get_bloginfo( 'name' ),
			'site_url'          => get_site_url(),
			'description'       => get_bloginfo( 'description' ),
			'wordpress_version' => get_bloginfo( 'version' ),
			'is_published'      => (bool) get_option( 'gdl_site_published', false ),
			// get_locale() is the canonical accessor since WP 4.0; the WPLANG option
			// is deprecated and often empty even on sites with a configured locale.
			'site_locale'       => get_locale(),
			// wp_timezone_string() (WP 5.3+) returns the named zone if set, else
			// converts gmt_offset to "+HH:MM" form. Reading timezone_string alone
			// returns "" for sites configured with a UTC offset instead of a city.
			'timezone'          => function_exists( 'wp_timezone_string' )
				? wp_timezone_string()
				: (string) get_option( 'timezone_string', '' ),
			'date_format'       => get_option( 'date_format', 'F j, Y' ),
			'time_format'       => get_option( 'time_format', 'g:i a' ),
			'posts_per_page'    => (int) get_option( 'posts_per_page', 10 ),
			'blog_public'       => (bool) get_option( 'blog_public', true ),
			'site_logo'         => $site_logo > 0 ? $site_logo : null,
			'site_icon'         => $site_icon > 0 ? $site_icon : null,
		);

		// Add post/page statistics if requested
		if ( ! empty( $input['include_stats'] ) ) {
			$result['stats'] = array(
				'post_count' => $this->get_published_post_count( 'post' ),
				'page_count' => $this->get_published_post_count( 'page' ),
			);
		}

		// Add theme information if requested
		if ( ! empty( $input['include_theme_info'] ) ) {
			$result['theme_info'] = $this->get_theme_info();
		}

		// Add plugin count if requested
		if ( ! empty( $input['include_plugin_count'] ) ) {
			$result['plugin_count'] = $this->get_active_plugin_count();
		}

		if ( ! empty( $input['include_reading_settings'] ) ) {
			$result['reading_settings'] = $this->get_reading_settings();
		}

		return $result;
	}

	/**
	 * Get reading settings (mirrors WordPress Settings > Reading)
	 *
	 * @return array Reading settings
	 */
	private function get_reading_settings(): array {
		$show_on_front       = get_option( 'show_on_front', 'posts' );
		$shows_page_on_front = 'page' === $show_on_front;

		$page_on_front  = $shows_page_on_front ? (int) get_option( 'page_on_front', 0 ) : 0;
		$page_for_posts = $shows_page_on_front ? (int) get_option( 'page_for_posts', 0 ) : 0;

		return array(
			'show_on_front'  => $show_on_front,
			'page_on_front'  => $page_on_front > 0 ? $page_on_front : null,
			'page_for_posts' => $page_for_posts > 0 ? $page_for_posts : null,
		);
	}

	/**
	 * Get published post count for a given post type
	 *
	 * @param string $post_type Post type
	 * @return int Published post count
	 */
	private function get_published_post_count( string $post_type ): int {
		$counts = wp_count_posts( $post_type );
		return isset( $counts->publish ) ? (int) $counts->publish : 0;
	}

	/**
	 * Get active theme information
	 *
	 * @return array Theme information
	 */
	private function get_theme_info(): array {
		$theme = wp_get_theme();

		return array(
			'name'    => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'author'  => $theme->get( 'Author' ),
		);
	}

	/**
	 * Get count of active plugins
	 *
	 * @return int Number of active plugins
	 */
	private function get_active_plugin_count(): int {
		if ( ! function_exists( 'get_plugins' ) ) {
			$this->load_admin_file( 'plugin.php' );
		}

		$active_plugins = get_option( 'active_plugins', array() );

		// Include network activated plugins if multisite
		if ( is_multisite() ) {
			$network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins  = array_merge( $active_plugins, $network_plugins );
		}

		return count( array_unique( $active_plugins ) );
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
