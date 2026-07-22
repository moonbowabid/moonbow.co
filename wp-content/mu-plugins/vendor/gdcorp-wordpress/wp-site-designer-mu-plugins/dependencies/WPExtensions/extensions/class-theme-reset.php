<?php
/**
 * Theme Reset
 *
 * Provides a REST endpoint to reset the theme.json user data back to defaults
 * by clearing the wp_global_styles custom post type content.
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
 * Provides a REST endpoint to reset theme.json user data back to defaults.
 */
class Theme_Reset {

	/**
	 * Initialize and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_action( 'rest_api_init', array( $instance, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'wp-site-designer/v1',
			'/reset-theme',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_reset' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'edit_theme_options' ) ) {
						return false;
					}
					$identifier = 'theme_reset_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 30, 300 ) ) {
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
			)
		);
	}

	/**
	 * Handle the theme reset request.
	 *
	 * @return \WP_REST_Response
	 */
	public function handle_reset(): \WP_REST_Response {
		$stylesheet = get_stylesheet();

		$global_styles_query = new \WP_Query(
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
						'terms'    => $stylesheet,
					),
				),
			)
		);

		$deleted = false;

		if ( $global_styles_query->have_posts() ) {
			$post = $global_styles_query->posts[0];

			Global_Styles_Sync::set_internal_update( true );
			try {
				$result = wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => '{"version": 3, "isGlobalStylesUserThemeJSON": true}',
					),
					true
				);
			} finally {
				Global_Styles_Sync::set_internal_update( false );
			}

			if ( is_wp_error( $result ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $result->get_error_message(),
					),
					500
				);
			}
			$deleted = true;
		}

		delete_option( Palette_Switcher::OPTION_KEY );
		delete_option( Font_Pairing::OPTION_KEY );
		delete_option( Style_Kit::OPTION_KEY );
		delete_option( Style_Kit::FRAGMENT_KEY );
		delete_option( Style_Kit::SNAPSHOT_KEY );
		delete_option( Logo_Settings::OPTION_KEY );
		// Clear the activated flag so the ?ai-action=generate page is accessible
		// again and the user can restart the generation flow.
		delete_option( 'wp_site_designer_activated' );

		Global_Styles_Sync::flush_theme_json_cache();

		return new \WP_REST_Response(
			array(
				'success'             => true,
				'global_styles_reset' => $deleted,
			),
			200
		);
	}
}
