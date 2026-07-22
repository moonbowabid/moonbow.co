<?php
/**
 * Style Kit
 *
 * Applies a comprehensive set of FSE style overrides (button shape, spacing,
 * image treatment, font size scale, shadows, custom CSS) via the
 * wp_theme_json_data_user filter. The kit data itself is owned by native-ui
 * and shipped via CDN — clients send the full theme.json fragment when
 * applying a kit so updates ship without a plugin release. PHP only stores
 * what was sent and applies it.
 *
 * On switch, also writes the full visual identity (kit fragment + bundled
 * palette/font) into the wp_global_styles post for editor parity.
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Utils\Rate_Limiter;
use WP_Error;

/**
 * Manages style kit selection via REST API and applies FSE style overrides
 * through theme.json user data using a fragment supplied by the client.
 */
class Style_Kit {

	public const OPTION_KEY   = 'airo_wp_active_style_kit';
	public const SNAPSHOT_KEY = 'airo_wp_pre_kit_snapshot';
	public const FRAGMENT_KEY = 'airo_wp_active_style_kit_fragment';

	/**
	 * Markers wrapped around the kit's CSS contribution inside the
	 * wp_global_styles post's `styles.css` field. They let us strip the
	 * previous kit's CSS on switch without touching CSS added by the user
	 * (or by site generation, system plugins, etc.).
	 */
	private const KIT_CSS_START_MARKER = '/* === AIRO_WP_STYLE_KIT_START === */';
	private const KIT_CSS_END_MARKER   = '/* === AIRO_WP_STYLE_KIT_END === */';

	/**
	 * Allowed top-level keys in a style kit theme.json fragment.
	 */
	private const ALLOWED_FRAGMENT_KEYS = array( 'version', 'settings', 'styles' );

	/**
	 * Maximum nesting depth permitted in a fragment payload. Theme.json
	 * fragments rarely exceed ~8 levels; this guards against pathological
	 * input without being restrictive in practice.
	 */
	private const MAX_FRAGMENT_DEPTH = 12;

	/**
	 * Initialize and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_action( 'rest_api_init', array( $instance, 'register_routes' ) );
		add_filter( 'wp_theme_json_data_user', array( $instance, 'apply_style_kit' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'wp-site-designer/v1',
			'/style-kit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_switch' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'style_kit_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 60, 300 ) ) {
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
				'args'                => array(
					'styleKit'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							// Slug is opaque to PHP — any reasonable identifier or 'none' is fine.
							return is_string( $value ) && '' !== $value && strlen( $value ) <= 64;
						},
					),
					'themeJson'   => array(
						'required' => false,
						'type'     => 'object',
					),
					'palette'     => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'fontPairing' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'wp-site-designer/v1',
			'/style-kit',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_active' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'style_kit_get_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 60, 60 ) ) { // 60 per minute.
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
			)
		);
	}

	/**
	 * Get the currently active style kit slug (REST callback).
	 *
	 * @return \WP_REST_Response
	 */
	public function get_active(): \WP_REST_Response {
		$slug = self::get_active_style_kit();
		return new \WP_REST_Response( array( 'styleKit' => $slug ), 200 );
	}

	/**
	 * Handle a style kit switch request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function handle_switch( \WP_REST_Request $request ): \WP_REST_Response {
		$slug = $request->get_param( 'styleKit' );

		if ( 'none' === $slug ) {
			delete_option( self::OPTION_KEY );
			delete_option( self::FRAGMENT_KEY );
			// Palette_Switcher and Font_Pairing each have their own
			// `wp_theme_json_data_user` filters keyed off their own option
			// keys. If we clear only Style_Kit's options here, those filters
			// keep applying the previously-active kit's palette and fonts at
			// runtime — producing a hybrid where the post_content has been
			// restored to the pre-kit snapshot but the rendered theme.json
			// still carries the kit's palette/typography overrides. Clear
			// them too so "Default" actually returns the site to its
			// pre-kit voice.
			delete_option( Palette_Switcher::OPTION_KEY );
			delete_option( Font_Pairing::OPTION_KEY );
			$restored = self::restore_pre_kit_snapshot();
			Global_Styles_Sync::flush_theme_json_cache();
			$response = array(
				'success'  => $restored,
				'styleKit' => 'none',
			);
			if ( ! $restored ) {
				// Options are cleared but the wp_global_styles post still
				// holds the previous kit's content. Filter path renders
				// "no kit" but the post-driven path will keep showing the
				// stale kit until the snapshot can be applied.
				$response['failedUpdates'] = array( 'globalStyles' );
			}
			return new \WP_REST_Response( $response, 200 );
		}

		$theme_json = $request->get_param( 'themeJson' );
		$fragment   = self::sanitize_fragment( $theme_json );
		if ( null === $fragment ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'invalid_theme_json',
					'message' => 'themeJson must be a theme.json fragment: required `version` (2 or 3) and at least one of `settings`/`styles` as an array.',
				),
				400
			);
		}

		// Write kit options with rollback on failure. WordPress does not provide
		// transactional semantics, but we can attempt cleanup if either write fails.
		// This handles transient failures (brief network blips, temporary DB load)
		// where rollback is more likely to succeed than the original write.
		$slug_ok     = update_option( self::OPTION_KEY, $slug, false );
		$fragment_ok = update_option( self::FRAGMENT_KEY, $fragment, false );

		if ( ! $slug_ok || ! $fragment_ok ) {
			// Rollback: clear both options to maintain consistency.
			delete_option( self::OPTION_KEY );
			delete_option( self::FRAGMENT_KEY );
			return new \WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'option_write_failed',
					'message' => 'Failed to store style kit options. Please try again.',
				),
				500
			);
		}

		$errors = array();

		$palette_slug      = $request->get_param( 'palette' );
		$font_pairing_slug = $request->get_param( 'fontPairing' );

		if ( is_string( $palette_slug ) && '' !== $palette_slug ) {
			if ( ! Palette_Switcher::set_active_palette( $palette_slug ) ) {
				$errors[] = 'palette';
			}
		}
		if ( is_string( $font_pairing_slug ) && '' !== $font_pairing_slug ) {
			if ( ! Font_Pairing::set_active_font_pairing( $font_pairing_slug ) ) {
				$errors[] = 'fontPairing';
			}
		}

		// Write kit styles into the global styles post so the editor
		// and frontend render identically from the same source. The
		// option keys above were already updated, so the filter path
		// (apply_style_kit) will render the new kit even if this fails;
		// the post-driven path won't, hence the failedUpdates entry.
		$global_styles_ok = self::apply_kit_to_global_styles(
			$fragment,
			is_string( $palette_slug ) ? $palette_slug : '',
			is_string( $font_pairing_slug ) ? $font_pairing_slug : ''
		);
		if ( ! $global_styles_ok ) {
			$errors[] = 'globalStyles';
		}

		Global_Styles_Sync::flush_theme_json_cache();

		$response = array(
			'success'  => empty( $errors ),
			'styleKit' => $slug,
		);
		if ( ! empty( $errors ) ) {
			$response['failedUpdates'] = $errors;
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Merge the active style kit fragment into theme.json via the user data filter.
	 *
	 * The fragment is whatever the client most recently sent on apply.
	 * Palette and font families are owned by Palette_Switcher and Font_Pairing.
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The incoming theme JSON data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function apply_style_kit( $theme_json ) {
		$slug = self::get_active_style_kit();
		if ( '' === $slug ) {
			return $theme_json;
		}

		$fragment = get_option( self::FRAGMENT_KEY );
		if ( ! is_array( $fragment ) || empty( $fragment ) ) {
			return $theme_json;
		}

		// CSS is owned exclusively by the wp_global_styles post (managed via
		// apply_kit_to_global_styles using marker-wrapped blocks so we never
		// clobber user-authored CSS). Strip css from the filter path so the
		// theme.json layer doesn't double-apply the kit's CSS or replace any
		// non-kit CSS the post already holds.
		if ( isset( $fragment['styles']['css'] ) ) {
			unset( $fragment['styles']['css'] );
		}

		return $theme_json->update_with( $fragment );
	}

	/**
	 * Validate and sanitize a theme.json fragment payload.
	 *
	 * Accepts only arrays with a subset of {version, settings, styles}.
	 * Rejects payloads that are too deeply nested or contain non-scalar
	 * leaf values other than arrays. Returns the sanitized array on success
	 * or null on failure.
	 *
	 * @param mixed $data The raw payload.
	 * @return array|null
	 */
	private static function sanitize_fragment( $data ): ?array {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return null;
		}

		$allowed_keys = array_flip( self::ALLOWED_FRAGMENT_KEYS );
		$filtered     = array_intersect_key( $data, $allowed_keys );

		if ( empty( $filtered ) ) {
			return null;
		}

		// Require a theme.json schema version (only 2 and 3 are accepted by
		// WordPress core). Without it the merge-into-post path can't be
		// trusted to produce a valid wp_global_styles document.
		if ( ! isset( $filtered['version'] ) || ! in_array( $filtered['version'], array( 2, 3 ), true ) ) {
			return null;
		}

		// settings/styles must be arrays when present — string/scalar payloads
		// would crash the merge logic in apply_kit_to_global_styles.
		if ( isset( $filtered['settings'] ) && ! is_array( $filtered['settings'] ) ) {
			return null;
		}
		if ( isset( $filtered['styles'] ) && ! is_array( $filtered['styles'] ) ) {
			return null;
		}

		// Require at least one of settings/styles to contain meaningful data —
		// a kit with only `version` is a no-op.
		$has_settings = ! empty( $filtered['settings'] );
		$has_styles   = ! empty( $filtered['styles'] );
		if ( ! $has_settings && ! $has_styles ) {
			return null;
		}

		if ( ! self::validate_depth( $filtered, 0 ) ) {
			return null;
		}

		return $filtered;
	}

	/**
	 * Recursively check that an array does not exceed MAX_FRAGMENT_DEPTH and
	 * contains only scalars/arrays at leaf positions.
	 *
	 * @param mixed $value The current node.
	 * @param int   $depth Current depth.
	 * @return bool
	 */
	private static function validate_depth( $value, int $depth ): bool {
		if ( $depth > self::MAX_FRAGMENT_DEPTH ) {
			return false;
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				if ( ! self::validate_depth( $child, $depth + 1 ) ) {
					return false;
				}
			}
			return true;
		}
		return is_scalar( $value ) || null === $value;
	}

	/**
	 * Sanitize CSS to prevent XSS via </style><script> breakout sequences.
	 *
	 * Defense in depth: strip HTML tags while preserving CSS syntax.
	 *
	 * @param string $css Raw CSS input.
	 * @return string Sanitized CSS.
	 */
	private static function sanitize_css( string $css ): string {
		return wp_strip_all_tags( $css );
	}

	/**
	 * Find the wp_global_styles post for the active theme.
	 *
	 * @return \WP_Post|null The global styles post, or null if not found.
	 */
	private static function get_global_styles_post(): ?\WP_Post {
		$query = new \WP_Query(
			array(
				'post_type'               => 'wp_global_styles',
				'post_status'             => array( 'publish', 'draft' ),
				'posts_per_page'          => 1,
				'no_found_rows'           => true,
				'update_post_meta_caches' => false,
				'update_post_term_caches' => false,
				'tax_query'               => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- required to find the correct wp_global_styles post for the active theme.
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => get_stylesheet(),
					),
				),
			)
		);

		return $query->have_posts() ? $query->posts[0] : null;
	}

	/**
	 * Save content to the global styles post.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $content The decoded post content to save.
	 * @return bool True on success, false if encode or update failed.
	 */
	private static function save_global_styles( int $post_id, array $content ): bool {
		$encoded = wp_json_encode( $content );
		if ( ! $encoded ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for failed JSON encode.
			error_log( 'Style_Kit: failed to encode global styles for post ' . $post_id );
			return false;
		}

		Global_Styles_Sync::set_internal_update( true );
		try {
			// wp_update_post runs wp_unslash then sanitization on post_content.
			// wp_slash ensures the JSON survives the round-trip intact.
			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $encoded ),
				),
				true
			);
		} finally {
			Global_Styles_Sync::set_internal_update( false );
		}

		if ( is_wp_error( $result ) || 0 === $result ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for failed wp_update_post.
			error_log( 'Style_Kit: wp_update_post failed for global styles post ' . $post_id );
			return false;
		}
		return true;
	}

	/**
	 * Write the kit's styles directly into the wp_global_styles post.
	 *
	 * This ensures the editor and frontend render identically — both read
	 * from the same post rather than fighting over filter vs post specificity.
	 *
	 * The post content is rebuilt from the pre-kit snapshot baseline on every
	 * apply, not patched on top of the current post. Without this rebuild,
	 * switching kit X → Y would leave any settings/element/block keys X
	 * defined that Y doesn't, and the post would slowly accumulate a chimera
	 * of every kit ever applied. The cost is that user edits to wp_global_styles
	 * made *while a kit is active* are lost on the next switch (except for
	 * non-kit CSS, which is preserved across switches via the marker block).
	 *
	 * TODO(concurrency): non-atomic read-modify-write on wp_global_styles.
	 * Two concurrent applies last-write-win, which is acceptable for this
	 * product (one user, one tab, one apply at a time). If we ever support
	 * multi-author concurrent editing, move to optimistic concurrency keyed
	 * on the post's post_modified timestamp.
	 *
	 * @param array  $kit               The sanitized kit fragment from the client.
	 * @param string $palette_slug      Bundled palette slug to merge in.
	 * @param string $font_pairing_slug Bundled font pairing slug to merge in.
	 * @return bool True on success, false if the post is missing or the save failed.
	 */
	private static function apply_kit_to_global_styles( array $kit, string $palette_slug, string $font_pairing_slug ): bool {
		$post = self::get_global_styles_post();
		if ( ! $post ) {
			return false;
		}

		// Snapshot the current post content before applying the FIRST kit so
		// we can restore it when switching back to "none". Subsequent kit
		// switches re-use this original snapshot as the rebuild baseline.
		if ( ! get_option( self::SNAPSHOT_KEY ) ) {
			update_option( self::SNAPSHOT_KEY, $post->post_content, false );
		}

		// Rebuild from the snapshot baseline rather than the current post.
		// Falls back to the current post if the snapshot can't be decoded —
		// preferable to producing an empty post.
		$snapshot_raw = get_option( self::SNAPSHOT_KEY );
		$baseline     = is_string( $snapshot_raw ) ? json_decode( $snapshot_raw, true ) : null;
		if ( ! is_array( $baseline ) ) {
			$baseline = json_decode( $post->post_content, true );
		}
		if ( ! is_array( $baseline ) ) {
			$baseline = array(
				'version'                     => 3,
				'isGlobalStylesUserThemeJSON' => true,
			);
		}

		$content = $baseline;

		// Merge kit's element-level button styles.
		if ( isset( $kit['styles']['elements']['button'] ) ) {
			$content['styles']['elements']['button'] = $kit['styles']['elements']['button'];
		}

		// Merge kit's block-level button styles (including variations).
		if ( isset( $kit['styles']['blocks']['core/button'] ) ) {
			$content['styles']['blocks']['core/button'] = $kit['styles']['blocks']['core/button'];
		}

		// Merge kit's element-level link, heading, caption styles.
		foreach ( array( 'link', 'heading', 'caption' ) as $element ) {
			if ( isset( $kit['styles']['elements'][ $element ] ) ) {
				$content['styles']['elements'][ $element ] = $kit['styles']['elements'][ $element ];
			}
		}

		// Merge kit's block-level styles (image, separator, quote, cover).
		if ( isset( $kit['styles']['blocks'] ) ) {
			foreach ( $kit['styles']['blocks'] as $block_name => $block_styles ) {
				$content['styles']['blocks'][ $block_name ] = $block_styles;
			}
		}

		// Merge kit's top-level typography (lineHeight).
		if ( isset( $kit['styles']['typography'] ) ) {
			if ( ! isset( $content['styles']['typography'] ) ) {
				$content['styles']['typography'] = array();
			}
			$content['styles']['typography'] = array_merge(
				$content['styles']['typography'],
				$kit['styles']['typography']
			);
		}

		// Merge kit's custom CSS without clobbering CSS that already exists
		// in the post. Non-kit CSS is preserved from the *current* post (so
		// user edits made during a kit-active session survive a kit switch),
		// not from the snapshot baseline (which represents the pre-kit state).
		// Strip any prior kit-managed block first, then re-attach a fresh
		// marker-wrapped block for the new kit.
		$current_content = json_decode( $post->post_content, true );
		$current_kit_css = is_array( $current_content ) && isset( $current_content['styles']['css'] ) && is_string( $current_content['styles']['css'] )
			? $current_content['styles']['css']
			: '';
		$preserved_css   = self::strip_kit_css_block( $current_kit_css );
		$kit_css_string  = isset( $kit['styles']['css'] ) && is_string( $kit['styles']['css'] )
			? self::sanitize_css( trim( $kit['styles']['css'] ) )
			: '';

		if ( '' !== $kit_css_string ) {
			$kit_block                = self::KIT_CSS_START_MARKER . "\n" . $kit_css_string . "\n" . self::KIT_CSS_END_MARKER;
			$content['styles']['css'] = '' === $preserved_css
				? $kit_block
				: $preserved_css . "\n\n" . $kit_block;
		} elseif ( '' !== $preserved_css ) {
			$content['styles']['css'] = $preserved_css;
		} elseif ( isset( $content['styles']['css'] ) ) {
			unset( $content['styles']['css'] );
		}

		// Merge kit's settings (fontSizes, spacing, layout). The wp_global_styles
		// post may store fontSizes/spacingSizes in origin-keyed format
		// (e.g. {"default": [...], "theme": [...]}). Using array_replace_recursive
		// would corrupt this structure by merging flat sequential arrays into
		// the origin-keyed object. Write each setting path explicitly under the
		// "theme" key when the existing value is origin-keyed, or replace
		// directly when it is a flat array.
		if ( isset( $kit['settings'] ) ) {
			if ( ! isset( $content['settings'] ) ) {
				$content['settings'] = array();
			}
			self::merge_settings_into_post( $content['settings'], $kit['settings'] );
		}

		// Merge bundled palette and font data into the post using flat arrays
		// (the wp_global_styles post stores theme.json v2 format, not origin-keyed).
		if ( '' !== $palette_slug ) {
			$palette_entries = Palette_Switcher::get_palette_for_slug( $palette_slug );
			if ( ! empty( $palette_entries ) ) {
				$content['settings']['color']['palette'] = $palette_entries;
			}
		}

		if ( '' !== $font_pairing_slug ) {
			$font_update = Font_Pairing::get_font_update_for_slug( $font_pairing_slug );
			if ( ! empty( $font_update ) ) {
				if ( isset( $font_update['settings']['typography']['fontFamilies'] ) ) {
					$content['settings']['typography']['fontFamilies'] = $font_update['settings']['typography']['fontFamilies'];
				}
				if ( isset( $font_update['styles']['typography']['fontFamily'] ) ) {
					$content['styles']['typography']['fontFamily'] = $font_update['styles']['typography']['fontFamily'];
				}
				if ( isset( $font_update['styles']['elements']['heading']['typography']['fontFamily'] ) ) {
					if ( ! isset( $content['styles']['elements']['heading']['typography'] ) ) {
						$content['styles']['elements']['heading']['typography'] = array();
					}
					$content['styles']['elements']['heading']['typography']['fontFamily'] = $font_update['styles']['elements']['heading']['typography']['fontFamily'];
				}
			}
		}

		return self::save_global_styles( $post->ID, $content );
	}

	/**
	 * Restore the global styles post to its pre-kit state.
	 *
	 * Uses the snapshot saved before the first kit was applied. Only
	 * deletes the snapshot option after a successful restore — failure
	 * paths preserve the snapshot so the user can retry. Returns true
	 * iff the post now reflects the snapshot (or no snapshot existed,
	 * which is also a no-op success).
	 *
	 * @return bool
	 */
	private static function restore_pre_kit_snapshot(): bool {
		$snapshot = get_option( self::SNAPSHOT_KEY );
		if ( ! $snapshot ) {
			return true;
		}

		$post = self::get_global_styles_post();
		if ( ! $post ) {
			// Don't drop the snapshot — the post may reappear (theme
			// switch, etc.) and we want a future "none" to revert
			// rather than silently no-op.
			return false;
		}

		// Validate the snapshot is valid JSON before restoring.
		$decoded = json_decode( $snapshot, true );
		if ( ! is_array( $decoded ) ) {
			// Snapshot is corrupt; leave it in place rather than
			// silently dropping it. Operator can clear the option
			// manually after diagnosing.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for corrupt snapshot.
			error_log( 'Style_Kit: pre-kit snapshot is not valid JSON; refusing to restore.' );
			return false;
		}

		if ( ! self::save_global_styles( $post->ID, $decoded ) ) {
			return false;
		}
		delete_option( self::SNAPSHOT_KEY );
		return true;
	}

	/**
	 * Merge kit settings into existing post settings without corrupting
	 * origin-keyed arrays (fontSizes, spacingSizes, etc.).
	 *
	 * The wp_global_styles post may store arrays like fontSizes in
	 * origin-keyed format: {"default": [...], "theme": [...]}. Flat
	 * sequential arrays from the kit are written under the "theme" key
	 * to match WordPress convention. Scalar settings (e.g. layout
	 * contentSize) are merged directly.
	 *
	 * @param array $post_settings Existing post settings (modified by reference).
	 * @param array $kit_settings  Kit settings to merge.
	 */
	private static function merge_settings_into_post( array &$post_settings, array $kit_settings ): void {
		// Settings paths that contain sequential arrays which must not be
		// merged with array_replace_recursive into origin-keyed structures.
		$array_paths = array(
			array( 'typography', 'fontSizes' ),
			array( 'spacing', 'spacingSizes' ),
		);

		// Work with a copy so we can remove handled paths without mutating
		// the input while we're still reading from it.
		$remaining = $kit_settings;

		foreach ( $array_paths as $path ) {
			// Read the value from the original kit settings.
			$kit_value = $kit_settings;
			foreach ( $path as $key ) {
				if ( ! isset( $kit_value[ $key ] ) ) {
					$kit_value = null;
					break;
				}
				$kit_value = $kit_value[ $key ];
			}

			if ( null === $kit_value ) {
				continue;
			}

			// Navigate to the parent in the post settings, creating as needed.
			$target = &$post_settings;
			foreach ( $path as $i => $key ) {
				if ( count( $path ) - 1 === $i ) {
					// Final key — check if the existing value is origin-keyed.
					if ( isset( $target[ $key ] ) && is_array( $target[ $key ] ) && ! wp_is_numeric_array( $target[ $key ] ) ) {
						// Origin-keyed: write under "theme".
						$target[ $key ]['theme'] = $kit_value;
					} else {
						// Flat or missing: replace directly.
						$target[ $key ] = $kit_value;
					}
				} else {
					if ( ! isset( $target[ $key ] ) ) {
						$target[ $key ] = array();
					}
					$target = &$target[ $key ];
				}
			}
			unset( $target );

			// Remove the handled path from the copy so it's not double-merged.
			$ref = &$remaining;
			foreach ( $path as $i => $key ) {
				if ( count( $path ) - 1 === $i ) {
					unset( $ref[ $key ] );
				} else {
					if ( ! isset( $ref[ $key ] ) ) {
						break;
					}
					$ref = &$ref[ $key ];
				}
			}
			unset( $ref );
		}

		// Merge remaining scalar/non-array settings directly.
		if ( ! empty( $remaining ) ) {
			$post_settings = array_replace_recursive( $post_settings, $remaining );
		}
	}

	/**
	 * Get the active style kit slug.
	 *
	 * @return string
	 */
	public static function get_active_style_kit(): string {
		return (string) get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Remove any prior kit-managed CSS block (delimited by KIT_CSS_*_MARKER)
	 * from a CSS string while leaving the rest untouched.
	 *
	 * Eats only the exact `\n\n` separator that apply_kit_to_global_styles
	 * inserts between preserved CSS and the kit block — bytes outside the
	 * marker block (including any leading/trailing whitespace authored by
	 * the user or by other writers) are preserved verbatim. On PCRE error
	 * the original CSS is returned unchanged so stale kit content surfaces
	 * for diagnosis rather than silently mutating user CSS.
	 *
	 * @param string $css The raw CSS string from the post.
	 * @return string The CSS with the marker-wrapped block stripped.
	 */
	private static function strip_kit_css_block( string $css ): string {
		if ( '' === $css ) {
			return '';
		}
		// Match the marker block plus the `\n\n` separator we insert before
		// it (when prior content exists). Falls back to a marker-only match
		// if the separator isn't there — e.g. when the kit block was the
		// only CSS in the post.
		$start  = preg_quote( self::KIT_CSS_START_MARKER, '/' );
		$end    = preg_quote( self::KIT_CSS_END_MARKER, '/' );
		$result = preg_replace(
			array(
				'/\\n\\n' . $start . '.*?' . $end . '/s',
				'/' . $start . '.*?' . $end . '/s',
			),
			'',
			$css
		);
		if ( ! is_string( $result ) ) {
			// preg_replace returns null on PCRE error (e.g. backtrack/recursion
			// limit). Surface this so stale kit CSS leaking into the post
			// doesn't go undiagnosed.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for PCRE failure.
			error_log( 'Style_Kit: preg_replace failed in strip_kit_css_block (PCRE error ' . preg_last_error() . ')' );
			return $css;
		}
		return $result;
	}
}
