<?php
/**
 * Palette Switcher
 *
 * Applies a color palette to the site via the wp_theme_json_data_user filter.
 * Stores the active palette slug in wp_options; on every request the filter
 * merges the palette colors into theme.json settings.color.palette at the
 * user layer so they override any colors set by site generation.
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
 * Manages color palette selection via REST API and applies it through theme.json user data.
 */
class Palette_Switcher {

	public const OPTION_KEY = 'wp_site_designer_active_palette';

	/**
	 * Palettes keyed by slug.
	 * Must stay in sync with packages/native-ui/src/data/palettes.ts.
	 *
	 * @var array<string, array{name: string, base: string, contrast: string, accent1: string, accent2: string, accent3: string, accent4: string, accent5: string}>
	 */
	private const PALETTES = array(
		'professional' => array(
			'name'     => 'Professional',
			'base'     => '#FFFFFF',
			'contrast' => '#111111',
			'accent1'  => '#9FC2E8',
			'accent2'  => '#6A8FE3',
			'accent3'  => '#0A1F44',
			'accent4'  => '#5F7F99',
			'accent5'  => '#F2F5F9',
		),
		'warm'         => array(
			'name'     => 'Warm',
			'base'     => '#F7F3E9',
			'contrast' => '#5D4427',
			'accent1'  => '#C65D2F',
			'accent2'  => '#E8A87C',
			'accent3'  => '#8B5E3C',
			'accent4'  => '#B8956E',
			'accent5'  => '#FDFBF5',
		),
		'natural'      => array(
			'name'     => 'Natural',
			'base'     => '#F5F3E8',
			'contrast' => '#2D4434',
			'accent1'  => '#8B9D7C',
			'accent2'  => '#B8A989',
			'accent3'  => '#4A6350',
			'accent4'  => '#D4C5A8',
			'accent5'  => '#FDFCF7',
		),
		'chic'         => array(
			'name'     => 'Chic',
			'base'     => '#F7EBE5',
			'contrast' => '#2B1F1F',
			'accent1'  => '#8B6B70',
			'accent2'  => '#C4534D',
			'accent3'  => '#5C4145',
			'accent4'  => '#A68B8F',
			'accent5'  => '#FCF6F4',
		),
		'friendly'     => array(
			'name'     => 'Friendly',
			'base'     => '#FFFFFF',
			'contrast' => '#1A1A1A',
			'accent1'  => '#5B9BD5',
			'accent2'  => '#0E8AAA',
			'accent3'  => '#1E3A5F',
			'accent4'  => '#89C4E1',
			'accent5'  => '#F5F9FC',
		),
		'modern'       => array(
			'name'     => 'Modern',
			'base'     => '#FFFFFF',
			'contrast' => '#1E2A3A',
			'accent1'  => '#6B5B8B',
			'accent2'  => '#8B7AB8',
			'accent3'  => '#4A3B6B',
			'accent4'  => '#5D6B88',
			'accent5'  => '#FAFAFE',
		),
		'classic'      => array(
			'name'     => 'Classic',
			'base'     => '#F5F3F1',
			'contrast' => '#4A4556',
			'accent1'  => '#A16E7C',
			'accent2'  => '#9C5969',
			'accent3'  => '#5E5468',
			'accent4'  => '#8C7E8F',
			'accent5'  => '#FAFAF9',
		),
		'playful'      => array(
			'name'     => 'Playful',
			'base'     => '#FFFEF8',
			'contrast' => '#1A1A1A',
			'accent1'  => '#FF8C42',
			'accent2'  => '#267872',
			'accent3'  => '#E85D23',
			'accent4'  => '#7DD3CE',
			'accent5'  => '#FFFEFB',
		),
		'luxurious'    => array(
			'name'     => 'Luxurious',
			'base'     => '#FBF8F3',
			'contrast' => '#2D0A0A',
			'accent1'  => '#8B2C3A',
			'accent2'  => '#C17B85',
			'accent3'  => '#5A1B25',
			'accent4'  => '#A65B66',
			'accent5'  => '#FFFDFB',
		),
		'bold'         => array(
			'name'     => 'Bold',
			'base'     => '#FFFFFF',
			'contrast' => '#1A1A1A',
			'accent1'  => '#E63946',
			'accent2'  => '#457B9D',
			'accent3'  => '#1D3557',
			'accent4'  => '#A8DADC',
			'accent5'  => '#F1FAEE',
		),
		'ocean'        => array(
			'name'     => 'Ocean',
			'base'     => '#FFFFFF',
			'contrast' => '#0B2027',
			'accent1'  => '#40798C',
			'accent2'  => '#70A9A1',
			'accent3'  => '#1B4965',
			'accent4'  => '#9EC5AB',
			'accent5'  => '#F0F7F4',
		),
		'golden'       => array(
			'name'     => 'Golden',
			'base'     => '#FFFDF5',
			'contrast' => '#2C1810',
			'accent1'  => '#D4A03C',
			'accent2'  => '#B8860B',
			'accent3'  => '#5C4017',
			'accent4'  => '#E8C872',
			'accent5'  => '#FFFEF8',
		),
		'monochrome'   => array(
			'name'     => 'Monochrome',
			'base'     => '#FFFFFF',
			'contrast' => '#111111',
			'accent1'  => '#555555',
			'accent2'  => '#888888',
			'accent3'  => '#333333',
			'accent4'  => '#AAAAAA',
			'accent5'  => '#F5F5F5',
		),
		'forest'       => array(
			'name'     => 'Forest',
			'base'     => '#F8F6F0',
			'contrast' => '#1A2E1A',
			'accent1'  => '#4A7C59',
			'accent2'  => '#2D5F3E',
			'accent3'  => '#1A3A26',
			'accent4'  => '#8FBC8F',
			'accent5'  => '#F7FAF5',
		),
		'sunset'       => array(
			'name'     => 'Sunset',
			'base'     => '#FFFBF5',
			'contrast' => '#2D1B0E',
			'accent1'  => '#E07A3A',
			'accent2'  => '#C84B31',
			'accent3'  => '#6B2D14',
			'accent4'  => '#F4A261',
			'accent5'  => '#FFF8F0',
		),
		'coastal'      => array(
			'name'     => 'Coastal',
			'base'     => '#FFFFFF',
			'contrast' => '#1C2B3A',
			'accent1'  => '#5B8FA8',
			'accent2'  => '#D4A574',
			'accent3'  => '#2C5F7C',
			'accent4'  => '#A8C8D8',
			'accent5'  => '#F4F8FA',
		),
		'electric'     => array(
			'name'     => 'Electric',
			'base'     => '#FFFFFF',
			'contrast' => '#0F0F1A',
			'accent1'  => '#6C5CE7',
			'accent2'  => '#00CEC9',
			'accent3'  => '#2D1B69',
			'accent4'  => '#A29BFE',
			'accent5'  => '#F5F3FF',
		),
		'terracotta'   => array(
			'name'     => 'Terracotta',
			'base'     => '#FBF5F0',
			'contrast' => '#3D2519',
			'accent1'  => '#C67B5C',
			'accent2'  => '#A0522D',
			'accent3'  => '#5C3321',
			'accent4'  => '#D4A68C',
			'accent5'  => '#FDF9F6',
		),
		'midnight'     => array(
			'name'     => 'Midnight',
			'base'     => '#F8F9FA',
			'contrast' => '#0D1117',
			'accent1'  => '#58A6FF',
			'accent2'  => '#388BFD',
			'accent3'  => '#161B22',
			'accent4'  => '#79C0FF',
			'accent5'  => '#F0F3F6',
		),
		'botanical'    => array(
			'name'     => 'Botanical',
			'base'     => '#FDFBF7',
			'contrast' => '#2B3A2B',
			'accent1'  => '#7B9E6B',
			'accent2'  => '#C4A35A',
			'accent3'  => '#3D5C3A',
			'accent4'  => '#B5C99A',
			'accent5'  => '#FAFDF5',
		),
		'coral'        => array(
			'name'     => 'Coral',
			'base'     => '#FFFFFF',
			'contrast' => '#1A1A2E',
			'accent1'  => '#FF6B6B',
			'accent2'  => '#EE5A6F',
			'accent3'  => '#2C2C54',
			'accent4'  => '#FFA07A',
			'accent5'  => '#FFF5F5',
		),
		'dark'         => array(
			'name'     => 'Dark',
			'base'     => '#121212',
			'contrast' => '#F5F5F5',
			'accent1'  => '#BB86FC',
			'accent2'  => '#03DAC6',
			'accent3'  => '#CF6679',
			'accent4'  => '#8AB4F8',
			'accent5'  => '#1E1E1E',
		),
		'dark-cinder'  => array(
			'name'     => 'Dark Cinder',
			'base'     => '#1A1210',
			'contrast' => '#FAF0E6',
			'accent1'  => '#FF6B35',
			'accent2'  => '#E8A87C',
			'accent3'  => '#FFB088',
			'accent4'  => '#C4704E',
			'accent5'  => '#241A16',
		),
		// Backwards compatibility: dark-ember was renamed to dark-cinder.
		'dark-ember'   => array(
			'name'     => 'Dark Cinder',
			'base'     => '#1A1210',
			'contrast' => '#FAF0E6',
			'accent1'  => '#FF6B35',
			'accent2'  => '#E8A87C',
			'accent3'  => '#FFB088',
			'accent4'  => '#C4704E',
			'accent5'  => '#241A16',
		),
		'dark-slate'   => array(
			'name'     => 'Dark Slate',
			'base'     => '#0F1923',
			'contrast' => '#E8ECF1',
			'accent1'  => '#64B5F6',
			'accent2'  => '#4DB6AC',
			'accent3'  => '#90CAF9',
			'accent4'  => '#80CBC4',
			'accent5'  => '#162030',
		),
		'memphis'      => array(
			'name'     => 'Memphis',
			'base'     => '#FFF8E7',
			'contrast' => '#161616',
			'accent1'  => '#FF3B6F',
			'accent2'  => '#00C2D1',
			'accent3'  => '#B88800',
			'accent4'  => '#7B5BFF',
			'accent5'  => '#FFFCEF',
		),
		'vaporwave'    => array(
			'name'     => 'Vaporwave',
			'base'     => '#F7EEFB',
			'contrast' => '#2A1B3D',
			'accent1'  => '#D32C7E',
			'accent2'  => '#006B85',
			'accent3'  => '#6B43B5',
			'accent4'  => '#FFB7E5',
			'accent5'  => '#FBF0FF',
		),
		'phosphor'     => array(
			'name'     => 'Phosphor',
			'base'     => '#0A0E0A',
			'contrast' => '#7BFF8E',
			'accent1'  => '#52E058',
			'accent2'  => '#FFB733',
			'accent3'  => '#3B7A40',
			'accent4'  => '#0F1A12',
			'accent5'  => '#0D120D',
		),
		'apothecary'   => array(
			'name'     => 'Apothecary',
			'base'     => '#F4ECDD',
			'contrast' => '#1C1410',
			'accent1'  => '#7A1A1A',
			'accent2'  => '#3F2A1F',
			'accent3'  => '#A88560',
			'accent4'  => '#D8C8A8',
			'accent5'  => '#FAF4E8',
		),
		'tatami'       => array(
			'name'     => 'Tatami',
			'base'     => '#F5EDE0',
			'contrast' => '#1A1816',
			'accent1'  => '#A03A28',
			'accent2'  => '#7E6E5B',
			'accent3'  => '#2E2A24',
			'accent4'  => '#C9BBA3',
			'accent5'  => '#FAF6EE',
		),
		'diner'        => array(
			'name'     => 'Diner',
			'base'     => '#FFF8EE',
			'contrast' => '#1A1A1A',
			'accent1'  => '#D62828',
			'accent2'  => '#0E5C7A',
			'accent3'  => '#5C0F0F',
			'accent4'  => '#8C5E0B',
			'accent5'  => '#FFFAF0',
		),
		'deco-night'   => array(
			'name'     => 'Deco Night',
			'base'     => '#100A0A',
			'contrast' => '#F0E5C8',
			'accent1'  => '#C8A24A',
			'accent2'  => '#8B0E0E',
			'accent3'  => '#F0E5C8',
			'accent4'  => '#5C2424',
			'accent5'  => '#1A0F0F',
		),
		'solar'        => array(
			'name'     => 'Solar',
			'base'     => '#FFFEF5',
			'contrast' => '#0D2B1F',
			'accent1'  => '#3A8C3A',
			'accent2'  => '#B07A00',
			'accent3'  => '#0D2B1F',
			'accent4'  => '#A8D5A8',
			'accent5'  => '#F5FBF1',
		),
		'pulp'         => array(
			'name'     => 'Pulp',
			'base'     => '#FFFAEB',
			'contrast' => '#0E0F19',
			'accent1'  => '#FF1744',
			'accent2'  => '#0091EA',
			'accent3'  => '#B07F00',
			'accent4'  => '#FFEFC9',
			'accent5'  => '#FFFEF7',
		),
		'sketchpad'    => array(
			'name'     => 'Sketchpad',
			'base'     => '#FAF6EC',
			'contrast' => '#1F1A14',
			'accent1'  => '#1F1A14',
			'accent2'  => '#C24A2C',
			'accent3'  => '#3B5C3B',
			'accent4'  => '#8B7E6A',
			'accent5'  => '#FDFAF2',
		),
		'plush'        => array(
			'name'     => 'Plush',
			'base'     => '#FFF6FB',
			'contrast' => '#3D2A50',
			'accent1'  => '#FF8FB1',
			'accent2'  => '#A892EE',
			'accent3'  => '#5BD0C2',
			'accent4'  => '#FFE4F0',
			'accent5'  => '#FFFAFD',
		),
	);

	/**
	 * Form field selectors that receive contrast-safe styling.
	 *
	 * Excludes interactive input types (submit, button, reset) and
	 * non-text inputs (checkbox, radio, file, image, hidden).
	 */
	private const FORM_FIELD_SELECTORS = array(
		'input:not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="image"]):not([type="hidden"])',
		'textarea',
		'select',
	);

	/**
	 * TT5 section background slug → default text slug mapping.
	 *
	 * Matches the Twenty Twenty-Five section style variation definitions.
	 * Used as the starting point; build_section_css() overrides the text
	 * color at runtime when the resolved palette would produce
	 * insufficient contrast.
	 */
	private const SECTION_DEFINITIONS = array(
		1 => array(
			'bg'   => 'accent-5',
			'text' => 'contrast',
		),
		2 => array(
			'bg'   => 'accent-2',
			'text' => 'contrast',
		),
		3 => array(
			'bg'   => 'accent-1',
			'text' => 'contrast',
		),
		4 => array(
			'bg'   => 'accent-3',
			'text' => 'accent-2',
		),
		5 => array(
			'bg'   => 'contrast',
			'text' => 'base',
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
		add_filter( 'wp_theme_json_data_user', array( $instance, 'apply_palette' ) );
		// Priority 99: must run after wp_enqueue_global_styles (priority 10) so our
		// !important overrides land after the global stylesheet in the <head>.
		add_action( 'wp_head', array( __CLASS__, 'print_contrast_fallback_css' ), 99 );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'wp-site-designer/v1',
			'/palette',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_switch' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'palette_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 30, 300 ) ) { // 30 per 5 minutes
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
				'args'                => array(
					'palette' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return 'none' === $value || isset( self::PALETTES[ $value ] );
						},
					),
				),
			)
		);

		register_rest_route(
			'wp-site-designer/v1',
			'/palette',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_active' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'palette_get_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 60, 60 ) ) { // 60 per minute.
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
			)
		);
	}

	/**
	 * Get the currently active palette slug (REST callback).
	 *
	 * @return \WP_REST_Response
	 */
	public function get_active(): \WP_REST_Response {
		$slug = self::get_active_palette();
		return new \WP_REST_Response( array( 'palette' => $slug ), 200 );
	}

	/**
	 * Handle a palette switch request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function handle_switch( \WP_REST_Request $request ): \WP_REST_Response {
		$slug = $request->get_param( 'palette' );

		if ( 'none' === $slug ) {
			delete_option( self::OPTION_KEY );
		} else {
			update_option( self::OPTION_KEY, $slug, false );
		}

		Global_Styles_Sync::flush_theme_json_cache();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'palette' => $slug,
			),
			200
		);
	}

	/**
	 * Merge the active palette into theme.json via the user data filter.
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The incoming theme JSON data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function apply_palette( $theme_json ) {
		$slug = self::get_active_palette();
		if ( ! $slug || ! isset( self::PALETTES[ $slug ] ) ) {
			return $theme_json;
		}

		return $theme_json->update_with(
			array(
				'version'  => 2,
				'settings' => array(
					'color' => array(
						'palette' => self::build_palette_from_colors( self::PALETTES[ $slug ] ),
					),
				),
			)
		);
	}

	/**
	 * Print contrast-safe fallback CSS for sections and form fields.
	 *
	 * Reads the resolved palette from the active palette source and
	 * computes contrast-safe text colors. If the default color pairing
	 * would produce insufficient contrast (< 4.5:1 WCAG AA), the text
	 * color is flipped to whichever of base/contrast provides better
	 * contrast against the background.
	 *
	 * Sections: fixes invisible section blocks when the AI generator
	 * emits orphaned has-background classes without actual color values.
	 *
	 * Form fields: ensures inputs, textareas, and selects always have
	 * readable text regardless of the palette (fixes white-on-white).
	 *
	 * @return void
	 */
	public static function print_contrast_fallback_css(): void {
		try {
			$palette_map = self::get_resolved_palette_map();
		} catch ( \Throwable $e ) {
			$palette_map = array();
		}

		$section_css = self::build_section_css( $palette_map );
		$form_css    = self::build_form_css( $palette_map );

		$css = $section_css . ' ' . $form_css;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS built from hardcoded slugs, not user input.
		echo '<style id="wp-site-designer-contrast-fallback">' . $css . '</style>' . "\n";
	}

	/**
	 * Build section variation fallback CSS rules.
	 *
	 * @param array<string,string> $palette_map Slug → hex map (empty if unavailable).
	 * @return string CSS rules for sections 1-5.
	 */
	private static function build_section_css( array $palette_map ): string {
		$rules = array();

		foreach ( self::SECTION_DEFINITIONS as $num => $def ) {
			$bg_slug   = $def['bg'];
			$text_slug = $def['text'];

			if ( ! empty( $palette_map ) ) {
				$text_slug = self::pick_contrast_safe_text( $bg_slug, $text_slug, $palette_map );
			}

			$base = sprintf( '.is-style-section-%d', $num );
			$root = sprintf(
				'%1$s,.has-background%1$s,.has-text-color%1$s',
				$base
			);

			$color_var = sprintf( 'var(--wp--preset--color--%s)', $text_slug );

			// Root container: set background + text color.
			$rules[] = sprintf(
				'%s{background-color:var(--wp--preset--color--%s) !important;color:%s !important;}',
				$root,
				$bg_slug,
				$color_var
			);

			// Child elements: boost specificity with .wp-site-blocks ancestor so
			// we beat WP global styles without needing !important, allowing future
			// style expansions to override these defaults.
			$rules[] = sprintf(
				'body .wp-site-blocks %1$s h1,body .wp-site-blocks %1$s h2,body .wp-site-blocks %1$s h3,body .wp-site-blocks %1$s h4,body .wp-site-blocks %1$s h5,body .wp-site-blocks %1$s h6,body .wp-site-blocks %1$s p,body .wp-site-blocks %1$s a:where(:not(.wp-element-button)),body .wp-site-blocks %1$s .wp-element-caption{color:%2$s;}',
				$base,
				$color_var
			);
		}

		return implode( ' ', $rules );
	}

	/**
	 * Build form field fallback CSS rules.
	 *
	 * Picks the lightest of base/contrast for the input background so
	 * fields are always visually distinct, then selects the opposite
	 * color for text. On light palettes this gives white inputs with
	 * dark text; on dark palettes it gives light inputs with dark text.
	 *
	 * @param array<string,string> $palette_map Slug → hex map (empty if unavailable).
	 * @return string CSS rules for form fields.
	 */
	private static function build_form_css( array $palette_map ): string {
		$bg_slug   = 'base';
		$text_slug = 'contrast';

		if ( ! empty( $palette_map ) ) {
			$base_hex     = $palette_map['base'] ?? null;
			$contrast_hex = $palette_map['contrast'] ?? null;

			// Use the lighter color as input background so fields stand out
			// on both light and dark page backgrounds.
			if ( $base_hex && $contrast_hex ) {
				$base_lum     = self::relative_luminance( $base_hex );
				$contrast_lum = self::relative_luminance( $contrast_hex );

				if ( $contrast_lum > $base_lum ) {
					$bg_slug   = 'contrast';
					$text_slug = 'base';
				}
			}

			$text_slug = self::pick_contrast_safe_text( $bg_slug, $text_slug, $palette_map );
		}

		$scoped_selectors = array();
		foreach ( self::FORM_FIELD_SELECTORS as $field ) {
			$scoped_selectors[] = 'body .wp-site-blocks ' . $field;
		}

		// Border uses color-mix of the text color at 30% to create a subtle
		// but always-visible boundary regardless of light/dark palette.
		// No !important: body + .wp-site-blocks scoping gives high specificity.
		$field_css = sprintf(
			'%s{background-color:var(--wp--preset--color--%s);color:var(--wp--preset--color--%s);border:1px solid color-mix(in srgb, var(--wp--preset--color--%s) 30%%, transparent);}',
			implode( ',', $scoped_selectors ),
			$bg_slug,
			$text_slug,
			$text_slug
		);

		// Give raw submit buttons the same visual treatment WordPress applies
		// to .wp-element-button so they look consistent regardless of whether
		// the AI added the class. TT5 default: accent-2 bg, base text.
		$btn_bg   = 'accent-2';
		$btn_text = 'base';

		if ( ! empty( $palette_map ) ) {
			$btn_text = self::pick_contrast_safe_text( $btn_bg, $btn_text, $palette_map );
		}

		$btn_selector = '.wp-site-blocks input[type="submit"]:not(.wp-element-button),'
			. '.wp-site-blocks button[type="submit"]:not(.wp-element-button)';
		$btn_css      = sprintf(
			'%s{background-color:var(--wp--preset--color--%s);color:var(--wp--preset--color--%s);'
			. 'border-width:0;padding:12px 30px;font-family:inherit;'
			. 'font-size:var(--wp--preset--font-size--medium, 1rem);line-height:inherit;cursor:pointer;}',
			$btn_selector,
			$btn_bg,
			$btn_text
		);

		return $field_css . ' ' . $btn_css;
	}

	/**
	 * Build a slug → hex map from the active palette.
	 *
	 * Checks three sources (without triggering the theme.json merge pipeline):
	 * 1. Our own PALETTES constant when a palette slug is active.
	 * 2. The wp_global_styles post (user overrides written by the AI generator).
	 * 3. The active theme's theme.json file on disk.
	 *
	 * @return array<string, string> Slug-keyed hex colors, e.g. ['base' => '#FFFFFF'].
	 */
	private static function get_resolved_palette_map(): array {
		$slug = self::get_active_palette();
		if ( $slug && isset( self::PALETTES[ $slug ] ) ) {
			return self::build_slug_hex_map( self::build_palette_from_colors( self::PALETTES[ $slug ] ) );
		}

		$map = self::read_palette_from_global_styles();
		if ( ! empty( $map ) ) {
			return $map;
		}

		return self::read_palette_from_theme_json();
	}

	/**
	 * Read palette colors from the wp_global_styles post.
	 *
	 * @return array<string, string> Slug-keyed hex colors.
	 */
	private static function read_palette_from_global_styles(): array {
		if ( ! function_exists( 'get_stylesheet' ) || ! class_exists( '\WP_Query' ) ) {
			return array();
		}

		$args = array(
			'post_type'               => 'wp_global_styles',
			'post_status'             => array( 'publish', 'draft' ),
			'posts_per_page'          => 1,
			'no_found_rows'           => true,
			'update_post_meta_caches' => false,
			'update_post_term_caches' => false,
			'tax_query'               => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- required to find the correct wp_global_styles post.
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => get_stylesheet(),
				),
			),
		);

		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return array();
		}

		$content = json_decode( $query->posts[0]->post_content, true );
		if ( ! is_array( $content ) ) {
			return array();
		}

		return self::extract_palette_from_settings( $content['settings'] ?? array() );
	}

	/**
	 * Read palette colors from the active theme's theme.json file.
	 *
	 * @return array<string, string> Slug-keyed hex colors.
	 */
	private static function read_palette_from_theme_json(): array {
		if ( ! function_exists( 'get_stylesheet_directory' ) ) {
			return array();
		}

		$path = get_stylesheet_directory() . '/theme.json';
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$content = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading local theme.json file, not a remote URL.
		if ( ! is_array( $content ) ) {
			return array();
		}

		return self::extract_palette_from_settings( $content['settings'] ?? array() );
	}

	/**
	 * Extract palette entries from a theme.json settings array.
	 *
	 * Handles both flat arrays and origin-keyed structures
	 * (custom > theme > default).
	 *
	 * @param array $settings The settings portion of theme.json data.
	 * @return array<string, string> Slug-keyed hex colors.
	 */
	private static function extract_palette_from_settings( array $settings ): array {
		$palette_data = $settings['color']['palette'] ?? array();

		if ( isset( $palette_data['custom'] ) && is_array( $palette_data['custom'] ) ) {
			return self::build_slug_hex_map( $palette_data['custom'] );
		}

		if ( isset( $palette_data['theme'] ) && is_array( $palette_data['theme'] ) ) {
			return self::build_slug_hex_map( $palette_data['theme'] );
		}

		if ( is_array( $palette_data ) && ! empty( $palette_data ) ) {
			return self::build_slug_hex_map( $palette_data );
		}

		return array();
	}

	/**
	 * Convert a palette array to a slug → hex map.
	 *
	 * @param array $palette Array of palette entries with slug and color keys.
	 * @return array<string, string> Slug-keyed hex colors.
	 */
	private static function build_slug_hex_map( array $palette ): array {
		$map = array();
		foreach ( $palette as $entry ) {
			if ( isset( $entry['slug'], $entry['color'] ) && is_string( $entry['color'] ) && 0 === strpos( $entry['color'], '#' ) ) {
				$map[ $entry['slug'] ] = $entry['color'];
			}
		}
		return $map;
	}

	/**
	 * Pick the text color slug that guarantees WCAG AA contrast (4.5:1).
	 *
	 * If the default text slug already provides sufficient contrast against
	 * the background, it is returned unchanged. Otherwise, whichever of
	 * 'base' or 'contrast' provides better contrast is returned.
	 *
	 * @param string               $bg_slug      Background color slug.
	 * @param string               $text_slug    Default text color slug from TT5.
	 * @param array<string,string> $palette_map  Slug → hex map.
	 * @return string The slug to use for text color.
	 */
	private static function pick_contrast_safe_text( string $bg_slug, string $text_slug, array $palette_map ): string {
		$bg_hex   = $palette_map[ $bg_slug ] ?? null;
		$text_hex = $palette_map[ $text_slug ] ?? null;

		if ( ! $bg_hex ) {
			return $text_slug;
		}

		if ( $text_hex && self::contrast_ratio( $bg_hex, $text_hex ) >= 4.5 ) {
			return $text_slug;
		}

		$base_hex     = $palette_map['base'] ?? null;
		$contrast_hex = $palette_map['contrast'] ?? null;

		$base_ratio     = $base_hex ? self::contrast_ratio( $bg_hex, $base_hex ) : 0;
		$contrast_ratio = $contrast_hex ? self::contrast_ratio( $bg_hex, $contrast_hex ) : 0;

		if ( $base_ratio >= $contrast_ratio && $base_ratio >= 4.5 ) {
			return 'base';
		}
		if ( $contrast_ratio >= 4.5 ) {
			return 'contrast';
		}

		return $base_ratio >= $contrast_ratio ? 'base' : 'contrast';
	}

	/**
	 * Calculate the WCAG 2.1 contrast ratio between two hex colors.
	 *
	 * @param string $hex1 First hex color.
	 * @param string $hex2 Second hex color.
	 * @return float Contrast ratio (1.0 to 21.0).
	 */
	private static function contrast_ratio( string $hex1, string $hex2 ): float {
		$l1 = self::relative_luminance( $hex1 );
		$l2 = self::relative_luminance( $hex2 );

		$lighter = max( $l1, $l2 );
		$darker  = min( $l1, $l2 );

		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * Calculate relative luminance per WCAG 2.1.
	 *
	 * @param string $hex Hex color (3 or 6 digits, with or without #).
	 * @return float Relative luminance (0.0 to 1.0).
	 */
	private static function relative_luminance( string $hex ): float {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

		$r = $r <= 0.04045 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
		$g = $g <= 0.04045 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
		$b = $b <= 0.04045 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

		return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	}

	/**
	 * Build the 12-entry palette array from a predefined color set.
	 *
	 * @param array $colors Associative array with base, contrast, accent1-5 keys.
	 * @return array The 12-entry palette array for theme.json.
	 */
	private static function build_palette_from_colors( array $colors ): array {
		return array(
			array(
				'color' => $colors['base'],
				'name'  => 'Base',
				'slug'  => 'base',
			),
			array(
				'color' => $colors['contrast'],
				'name'  => 'Contrast',
				'slug'  => 'contrast',
			),
			array(
				'color' => $colors['contrast'],
				'name'  => 'Contrast 3',
				'slug'  => 'contrast-3',
			),
			array(
				'color' => $colors['accent1'],
				'name'  => 'Accent 1',
				'slug'  => 'accent-1',
			),
			array(
				'color' => $colors['accent2'],
				'name'  => 'Accent 2',
				'slug'  => 'accent-2',
			),
			array(
				'color' => $colors['base'],
				'name'  => 'Base 2',
				'slug'  => 'base-2',
			),
			array(
				'color' => $colors['accent3'],
				'name'  => 'Accent 3',
				'slug'  => 'accent-3',
			),
			array(
				'color' => $colors['accent4'],
				'name'  => 'Accent 4',
				'slug'  => 'accent-4',
			),
			array(
				'color' => $colors['accent5'],
				'name'  => 'Accent 5',
				'slug'  => 'accent-5',
			),
			array(
				'color' => self::hex_to_rgba( $colors['contrast'], 0.2 ),
				'name'  => 'Accent 6',
				'slug'  => 'accent-6',
			),
			array(
				'color' => $colors['accent1'],
				'name'  => 'Accent',
				'slug'  => 'accent',
			),
			array(
				'color' => $colors['base'],
				'name'  => 'Background',
				'slug'  => 'background',
			),
		);
	}

	/**
	 * Get the active palette slug.
	 *
	 * @return string
	 */
	public static function get_active_palette(): string {
		return (string) get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Get the 12-entry palette array for a given predefined slug.
	 *
	 * Used by Style_Kit to bundle palette colors into the kit fragment.
	 *
	 * @param string $slug A valid palette slug.
	 * @return array The 12-entry palette array, or empty array if slug not found.
	 */
	public static function get_palette_for_slug( string $slug ): array {
		if ( ! isset( self::PALETTES[ $slug ] ) ) {
			return array();
		}
		return self::build_palette_from_colors( self::PALETTES[ $slug ] );
	}

	/**
	 * Set the active palette slug.
	 *
	 * @param string $slug A valid palette slug or 'none' to clear.
	 * @return bool True on success, false on failure.
	 */
	public static function set_active_palette( string $slug ): bool {
		if ( 'none' === $slug ) {
			return delete_option( self::OPTION_KEY );
		}
		// Reject unknown slugs so direct callers (e.g. Style_Kit) can't
		// store garbage that later silently fails to apply.
		if ( ! isset( self::PALETTES[ $slug ] ) ) {
			return false;
		}
		return update_option( self::OPTION_KEY, $slug, false );
	}

	/**
	 * Convert a hex colour to an rgba() string at the given alpha.
	 *
	 * @param string $hex   A 3- or 6-digit hex colour (with or without #).
	 * @param float  $alpha Opacity between 0 and 1.
	 * @return string       CSS rgba() value, e.g. "rgba(17, 17, 17, 0.2)".
	 */
	private static function hex_to_rgba( string $hex, float $alpha ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $alpha );
	}
}
