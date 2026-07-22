<?php
/**
 * Logo Settings
 *
 * Automatically hides the site title block on the frontend whenever a
 * logo has been uploaded. Persists the preference in a separate option
 * so the title stays hidden even after the logo is removed.
 *
 * Uses the render_block filter on core/site-title so it works correctly
 * with block themes (FSE) where the classic display_header_text theme
 * mod has no effect.
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hides site title block when a logo has been uploaded.
 */
class Logo_Settings {

	public const OPTION_KEY = 'wp_site_designer_hide_title';

	/**
	 * Initialize and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_action( 'update_option_site_logo', array( $instance, 'on_logo_set' ), 10, 2 );
		add_action( 'add_option_site_logo', array( $instance, 'on_logo_added' ), 10, 2 );
		add_filter( 'render_block_core/site-title', array( $instance, 'maybe_hide_site_title' ), 10, 2 );
		add_action( 'wp_head', array( $instance, 'inject_logo_constraints' ), 999 );
	}

	/**
	 * When the site_logo option is updated to a non-zero attachment ID,
	 * persist the "hide title" preference.
	 *
	 * @param mixed $old_value Previous value.
	 * @param mixed $new_value New value (attachment ID or 0).
	 * @return void
	 */
	public function on_logo_set( $old_value, $new_value ): void {
		if ( is_numeric( $new_value ) && (int) $new_value > 0 ) {
			update_option( self::OPTION_KEY, '1', false );
		}
	}

	/**
	 * When the site_logo option is created with a non-zero attachment ID,
	 * persist the "hide title" preference. Covers the first-time logo set
	 * case where the option didn't previously exist.
	 *
	 * @param string $option Option name (always 'site_logo').
	 * @param mixed  $value  New value (attachment ID or 0).
	 * @return void
	 */
	public function on_logo_added( $option, $value ): void {
		if ( is_numeric( $value ) && (int) $value > 0 ) {
			update_option( self::OPTION_KEY, '1', false );
		}
	}

	/**
	 * Suppress the core/site-title block output when the hide-title
	 * preference is active.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The parsed block data.
	 * @return string Empty string when title is hidden, original content otherwise.
	 */
	public function maybe_hide_site_title( string $block_content, array $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress render_block filter requires both parameters.
		if ( '1' === get_option( self::OPTION_KEY ) ) {
			return '';
		}

		return $block_content;
	}

	/**
	 * Inject CSS to constrain logos to max 180px × 80px, preventing oversized headers.
	 *
	 * 180px width matches existing logo block default; 80px height keeps headers
	 * under ~10-12% of typical viewport height, addressing sticky header UX issues.
	 * Uses !important only on width/height to override WordPress inline attributes,
	 * allowing sites to customize max-width/max-height constraints if needed.
	 *
	 * @return void
	 */
	public function inject_logo_constraints(): void {
		?>
		<style id="site-designer-logo-constraints">
			.wp-block-site-logo img {
				max-width: 180px;
				max-height: 80px;
				width: auto !important;
				height: auto !important;
				object-fit: contain;
			}
		</style>
		<?php
	}
}
