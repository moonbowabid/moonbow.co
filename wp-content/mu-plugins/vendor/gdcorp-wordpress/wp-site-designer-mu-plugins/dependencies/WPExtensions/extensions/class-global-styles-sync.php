<?php
/**
 * Global Styles Sync
 *
 * Ensures a single source of truth for design data (palette, fonts) by
 * coordinating between two systems that can set styles:
 *
 * 1. The UI (native-ui) — stores a slug in wp_options, applied via theme.json filters
 * 2. The API (site-designer-api) — writes directly to the wp_global_styles post
 *
 * When the API writes new palette/font data to the global styles post, this
 * class clears the UI's stale slug options so the theme.json filters step
 * aside and let the API-written data flow through. The API data in the post
 * is preserved as the "Default" fallback when no UI slug is active.
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates design data between UI slug options and API-written global styles.
 */
class Global_Styles_Sync {

	/**
	 * Flag to suppress the save_post hook during internal updates.
	 *
	 * @var bool
	 */
	private static bool $is_internal_update = false;

	/**
	 * Flag set when the current REST request targets the WP core
	 * global-styles endpoint (i.e. the FSE / block editor is saving).
	 *
	 * @var bool
	 */
	private static bool $is_editor_save = false;

	/**
	 * Initialize and register hooks.
	 */
	public static function init(): void {
		add_action( 'save_post_wp_global_styles', array( self::class, 'on_global_styles_save' ), 10, 1 );
		add_filter( 'rest_pre_dispatch', array( self::class, 'detect_editor_save' ), 10, 3 );
		add_action( 'rest_after_insert_wp_global_styles', array( self::class, 'on_rest_after_insert' ), 10, 0 );
	}

	/**
	 * Mark the beginning/end of an internal global styles update.
	 *
	 * Call this before any wp_update_post on the wp_global_styles post from
	 * within our own code (e.g. Theme_Reset) to prevent the save hook from
	 * clearing slug options.
	 *
	 * @param bool $is_internal Whether the current update is internal.
	 */
	public static function set_internal_update( bool $is_internal ): void {
		self::$is_internal_update = $is_internal;
	}

	/**
	 * Reset all state flags. Intended for unit tests only.
	 */
	public static function reset_state(): void {
		self::$is_internal_update = false;
		self::$is_editor_save     = false;
	}

	/**
	 * Detect when the current REST request is a core global-styles save
	 * (FSE / block editor). MCP tools use different routes, so they
	 * are not flagged.
	 *
	 * @param mixed            $result  Response to replace the requested one.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed Unmodified $result.
	 */
	public static function detect_editor_save( $result, $server, $request ) {
		$route = $request->get_route();
		if ( 0 === strpos( $route, '/wp/v2/global-styles' ) ) {
			self::$is_editor_save = true;
		}
		return $result;
	}

	/**
	 * Handle wp_global_styles post saves from external sources (API/MCP).
	 *
	 * When the global styles post is updated externally, clear any stale
	 * slug options so the theme.json filters step aside and let the
	 * post data flow through naturally.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function on_global_styles_save( int $post_id ): void {
		if ( self::$is_internal_update || self::$is_editor_save ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return;
		}

		$content = json_decode( $post->post_content, true );
		if ( ! is_array( $content ) ) {
			return;
		}

		// Only clear slug options for data types the API actually wrote.
		$has_palette = self::post_has_palette( $content );
		$has_fonts   = self::post_has_fonts( $content );

		if ( $has_palette ) {
			delete_option( Palette_Switcher::OPTION_KEY );
		}
		if ( $has_fonts ) {
			delete_option( Font_Pairing::OPTION_KEY );
		}
		if ( $has_palette || $has_fonts ) {
			delete_option( Style_Kit::OPTION_KEY );
			delete_option( Style_Kit::FRAGMENT_KEY );
			// Invalidate the pre-kit snapshot since the API is writing new
			// base styles that supersede whatever was snapshotted.
			delete_option( Style_Kit::SNAPSHOT_KEY );
			self::flush_theme_json_cache();
		}
	}

	/**
	 * Bust theme.json caches after every REST API save of the wp_global_styles post.
	 *
	 * Fires for both create and update operations and does not check
	 * is_editor_save or is_internal_update, so a GET immediately after a PUT
	 * via the WP REST API always sees the committed state.
	 */
	public static function on_rest_after_insert(): void {
		self::flush_theme_json_cache();
	}

	/**
	 * Clear WordPress theme.json caches so the next page load picks up changes.
	 */
	public static function flush_theme_json_cache(): void {
		wp_clean_themes_cache();
		if ( class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}

	/**
	 * Check if global styles content contains palette data.
	 *
	 * @param array $content Decoded post content.
	 * @return bool
	 */
	private static function post_has_palette( array $content ): bool {
		$palette = $content['settings']['color']['palette'] ?? null;
		return is_array( $palette ) && ! empty( $palette );
	}

	/**
	 * Check if global styles content contains font data.
	 *
	 * @param array $content Decoded post content.
	 * @return bool
	 */
	private static function post_has_fonts( array $content ): bool {
		$fonts = $content['settings']['typography']['fontFamilies'] ?? null;
		return is_array( $fonts ) && ! empty( $fonts );
	}
}
