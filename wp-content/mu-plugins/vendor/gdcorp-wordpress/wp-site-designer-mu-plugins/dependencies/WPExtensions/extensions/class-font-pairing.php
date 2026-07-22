<?php
/**
 * Font Pairing
 *
 * Applies a font pairing to the site via the wp_theme_json_data_user filter.
 * Stores the active pairing slug in wp_options; on every request the filter
 * merges typography settings (font families + element styles) into theme.json
 * at the user layer so they override any fonts set by site generation.
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
 * Manages font pairing selection via REST API and applies it through theme.json user data.
 */
class Font_Pairing {

	public const OPTION_KEY = 'wp_site_designer_active_font_pairing';

	/**
	 * Fonts that use a serif generic fallback instead of sans-serif.
	 *
	 * @var string[]
	 */
	private const SERIF_FONTS = array(
		'Playfair Display',
		'Cormorant Garamond',
		'Libre Baskerville',
		'Fraunces',
		'DM Serif Display',
		'Abril Fatface',
		'IM Fell English SC',
		'Lora',
		'Shippori Mincho',
		'Marcellus',
		'Roboto Slab',
	);

	/**
	 * Font pairings keyed by slug.
	 * Must stay in sync with packages/native-ui/src/data/fontPairings.ts.
	 *
	 * @var array<string, array{heading: string, body: string}>
	 */
	private const PAIRINGS = array(
		'classic-serif'    => array(
			'heading' => 'Playfair Display',
			'body'    => 'Source Sans 3',
		),
		'modern-sans'      => array(
			'heading' => 'Inter',
			'body'    => 'Inter',
		),
		'editorial'        => array(
			'heading' => 'DM Serif Display',
			'body'    => 'DM Sans',
		),
		'geometric'        => array(
			'heading' => 'Poppins',
			'body'    => 'Open Sans',
		),
		'humanist'         => array(
			'heading' => 'Nunito',
			'body'    => 'Nunito Sans',
		),
		'elegant'          => array(
			'heading' => 'Cormorant Garamond',
			'body'    => 'Proza Libre',
		),
		'bold-impact'      => array(
			'heading' => 'Oswald',
			'body'    => 'Roboto',
		),
		'warm-friendly'    => array(
			'heading' => 'Quicksand',
			'body'    => 'Cabin',
		),
		'professional'     => array(
			'heading' => 'Montserrat',
			'body'    => 'Lato',
		),
		'tech'             => array(
			'heading' => 'Space Grotesk',
			'body'    => 'IBM Plex Sans',
		),
		'organic'          => array(
			'heading' => 'Fraunces',
			'body'    => 'Commissioner',
		),
		'minimalist'       => array(
			'heading' => 'Work Sans',
			'body'    => 'Work Sans',
		),
		'luxury'           => array(
			'heading' => 'Libre Baskerville',
			'body'    => 'Source Sans 3',
		),
		'retro'            => array(
			'heading' => 'Abril Fatface',
			'body'    => 'Poppins',
		),
		'playful'          => array(
			'heading' => 'Baloo 2',
			'body'    => 'Nunito',
		),
		'condensed'        => array(
			'heading' => 'Barlow Condensed',
			'body'    => 'Barlow',
		),
		'cyber'            => array(
			'heading' => 'Orbitron',
			'body'    => 'Space Grotesk',
		),
		'memphis-display'  => array(
			'heading' => 'Archivo Black',
			'body'    => 'Inter',
		),
		'mono'             => array(
			'heading' => 'JetBrains Mono',
			'body'    => 'JetBrains Mono',
		),
		'apothecary-serif' => array(
			'heading' => 'IM Fell English SC',
			'body'    => 'Lora',
		),
		'tatami-mincho'    => array(
			'heading' => 'Shippori Mincho',
			'body'    => 'Inter',
		),
		'diner-script'     => array(
			'heading' => 'Lobster',
			'body'    => 'Roboto Slab',
		),
		'deco-display'     => array(
			'heading' => 'Poiret One',
			'body'    => 'Marcellus',
		),
		'pulp-comic'       => array(
			'heading' => 'Bangers',
			'body'    => 'Open Sans',
		),
		'sketch-hand'      => array(
			'heading' => 'Caveat',
			'body'    => 'Patrick Hand',
		),
		'plush-bubble'     => array(
			'heading' => 'Fredoka',
			'body'    => 'Nunito',
		),
	);

	/**
	 * Initialize and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_action( 'rest_api_init', array( $instance, 'register_routes' ) );
		add_filter( 'wp_theme_json_data_user', array( $instance, 'apply_fonts' ) );
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_google_fonts' ) );
		add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_google_fonts' ) );
		// `enqueue_block_assets` fires inside the block editor iframe (Site
		// Editor / Post Editor). Without this, the Google Fonts stylesheet
		// loads on the front end but not in the editor, so display fonts
		// like Bangers (pulp) fall back to a plain sans-serif inside the
		// editor — visually mismatching the front-end render. wp_enqueue_style
		// is idempotent on the same handle, so this is safe alongside the
		// front-end / admin hooks above.
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_google_fonts' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'wp-site-designer/v1',
			'/font-pairing',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_switch' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'font_pairing_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 30, 300 ) ) { // 30 per 5 minutes.
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
				'args'                => array(
					'fontPairing' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return 'none' === $value || isset( self::PAIRINGS[ $value ] );
						},
					),
				),
			)
		);

		register_rest_route(
			'wp-site-designer/v1',
			'/font-pairing',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_active_pairing' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'font_pairing_get_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 60, 60 ) ) { // 60 per minute.
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
			)
		);
	}

	/**
	 * Handle a font pairing switch request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function handle_switch( \WP_REST_Request $request ): \WP_REST_Response {
		$slug = $request->get_param( 'fontPairing' );

		if ( 'none' === $slug ) {
			delete_option( self::OPTION_KEY );
		} else {
			update_option( self::OPTION_KEY, $slug, false );
		}

		Global_Styles_Sync::flush_theme_json_cache();

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'fontPairing' => $slug,
			),
			200
		);
	}

	/**
	 * Merge the active font pairing into theme.json via the user data filter.
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The incoming theme JSON data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function apply_fonts( $theme_json ) {
		$slug = self::get_active_font_pairing();
		if ( ! $slug || ! isset( self::PAIRINGS[ $slug ] ) ) {
			return $theme_json;
		}

		return $theme_json->update_with( self::build_font_update_from_pairing( self::PAIRINGS[ $slug ] ) );
	}

	/**
	 * Build a theme.json update array from a predefined font pairing.
	 *
	 * @param array $pairing Associative array with heading and body keys.
	 * @return array The theme.json update array.
	 */
	private static function build_font_update_from_pairing( array $pairing ): array {
		$heading_generic = in_array( $pairing['heading'], self::SERIF_FONTS, true ) ? 'serif' : 'sans-serif';
		$body_generic    = in_array( $pairing['body'], self::SERIF_FONTS, true ) ? 'serif' : 'sans-serif';
		$heading_css     = '"' . $pairing['heading'] . '", ' . $heading_generic;
		$body_css        = '"' . $pairing['body'] . '", ' . $body_generic;

		$font_families = array();

		$heading_slug    = sanitize_title( $pairing['heading'] );
		$font_families[] = array(
			'fontFamily' => $heading_css,
			'name'       => $pairing['heading'],
			'slug'       => $heading_slug,
		);

		$body_slug = sanitize_title( $pairing['body'] );
		if ( $body_slug !== $heading_slug ) {
			$font_families[] = array(
				'fontFamily' => $body_css,
				'name'       => $pairing['body'],
				'slug'       => $body_slug,
			);
		}

		return array(
			'version'  => 2,
			'settings' => array(
				'typography' => array(
					'fontFamilies' => $font_families,
				),
			),
			'styles'   => array(
				'typography' => array(
					'fontFamily' => $body_css,
				),
				'elements'   => array(
					'heading' => array(
						'typography' => array(
							'fontFamily' => $heading_css,
						),
					),
				),
			),
		);
	}

	/**
	 * Get the theme.json font update array for a given predefined slug.
	 *
	 * Used by Style_Kit to bundle font data into the kit fragment.
	 *
	 * @param string $slug A valid font pairing slug.
	 * @return array The theme.json update array, or empty array if slug not found.
	 */
	public static function get_font_update_for_slug( string $slug ): array {
		if ( ! isset( self::PAIRINGS[ $slug ] ) ) {
			return array();
		}
		return self::build_font_update_from_pairing( self::PAIRINGS[ $slug ] );
	}

	/**
	 * Enqueue the Google Fonts stylesheet for the active font pairing.
	 *
	 * @return void
	 */
	public function enqueue_google_fonts(): void {
		$slug = self::get_active_font_pairing();
		if ( ! $slug || ! isset( self::PAIRINGS[ $slug ] ) ) {
			return;
		}

		$pairing  = self::PAIRINGS[ $slug ];
		$families = array( rawurlencode( $pairing['heading'] ) . ':wght@400;500;600;700' );

		if ( $pairing['body'] !== $pairing['heading'] ) {
			$families[] = rawurlencode( $pairing['body'] ) . ':wght@400;500;600;700';
		}

		$url = 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $families ) . '&display=swap';

		// Version must be null to prevent wp_enqueue_style from running
		// add_query_arg(), which uses parse_str() internally — PHP's parse_str()
		// drops duplicate query parameter names, destroying the second family= param.
		wp_enqueue_style( 'site-designer-font-pairing', $url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- null prevents add_query_arg() from mangling duplicate family= params.

		static $preconnect_added = false;
		if ( ! $preconnect_added ) {
			$preconnect_added = true;
			add_filter(
				'wp_resource_hints',
				static function ( array $urls, string $relation_type ): array {
					if ( 'preconnect' === $relation_type ) {
						$urls[] = array(
							'href'        => 'https://fonts.googleapis.com',
							'crossorigin' => 'anonymous',
						);
						$urls[] = array(
							'href'        => 'https://fonts.gstatic.com',
							'crossorigin' => 'anonymous',
						);
					}
					return $urls;
				},
				10,
				2
			);
		}
	}

	/**
	 * Get the currently active font pairing slug (REST callback).
	 *
	 * @return \WP_REST_Response
	 */
	public function get_active_pairing(): \WP_REST_Response {
		$slug = self::get_active_font_pairing();
		return new \WP_REST_Response( array( 'fontPairing' => $slug ), 200 );
	}

	/**
	 * Get the active font pairing slug from the wp_options table.
	 *
	 * @return string
	 */
	public static function get_active_font_pairing(): string {
		return (string) get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Set the active font pairing slug.
	 *
	 * @param string $slug A valid pairing slug or 'none' to clear.
	 * @return bool True on success, false on failure.
	 */
	public static function set_active_font_pairing( string $slug ): bool {
		if ( 'none' === $slug ) {
			return delete_option( self::OPTION_KEY );
		}
		// Reject unknown slugs so direct callers (e.g. Style_Kit) can't
		// store garbage that later silently fails to apply.
		if ( ! isset( self::PAIRINGS[ $slug ] ) ) {
			return false;
		}
		return update_option( self::OPTION_KEY, $slug, false );
	}
}
