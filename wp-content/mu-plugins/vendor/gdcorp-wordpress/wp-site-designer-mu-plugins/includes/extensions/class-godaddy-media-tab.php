<?php
/**
 * GoDaddy Media Tab Extension
 *
 * Adds a "GoDaddy Media" tab to the WordPress media modal (wp.media) that
 * enables users to browse their GoDaddy Media Library via the parent MFE's
 * MediaManager component. Selected assets are sideloaded into WordPress as
 * real attachments. Only active in iframe (Site Designer) context.
 *
 * This extension enqueues the JavaScript/CSS for the tab and provides the
 * configuration (sideload endpoint URL, nonce, parent origin) needed for
 * cross-origin communication and REST API calls.
 *
 * @package wp-site-designer-mu-plugins
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Extensions;

use GoDaddy\WordPress\Plugins\SiteDesigner\Utils\Iframe_Context_Detector;

/**
 * Manages the GoDaddy Media Library tab in wp.media
 */
class GoDaddy_Media_Tab {

	/**
	 * Script handle for the GoDaddy media tab JavaScript
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'gdmu-site-designer-godaddy-media-tab';

	/**
	 * Style handle for the GoDaddy media tab CSS
	 *
	 * @var string
	 */
	private const STYLE_HANDLE = 'gdmu-site-designer-godaddy-media-tab';

	/**
	 * Initialize the class and register all hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();

		add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets when user has upload permissions
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->user_can_upload() ) {
			return;
		}

		if ( $this->enqueue_script() ) {
			$this->enqueue_style();
		}
	}

	/**
	 * Enqueue the GoDaddy media tab JavaScript and attach inline config
	 *
	 * Uses wp_add_inline_script with position 'before' to guarantee the config
	 * object exists before the script executes, regardless of hook priority.
	 *
	 * @return bool True if the script was enqueued successfully.
	 */
	private function enqueue_script(): bool {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return true;
		}

		$script_path = GDMU_SITE_DESIGNER_PATH . '/assets/js/godaddy-media-tab.js';
		$script_url  = GDMU_SITE_DESIGNER_URL . '/assets/js/godaddy-media-tab.js';

		if ( ! file_exists( $script_path ) ) {
			$this->log_error( 'JavaScript file not found', $script_path );
			return false;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$script_url,
			array( 'media-editor', 'media-views', 'backbone', 'underscore', 'wp-api-request' ),
			GDMU_SITE_DESIGNER_VERSION,
			true
		);

		$this->add_inline_config();

		return true;
	}

	/**
	 * Enqueue the GoDaddy media tab CSS
	 *
	 * @return void
	 */
	private function enqueue_style(): void {
		if ( wp_style_is( self::STYLE_HANDLE, 'enqueued' ) ) {
			return;
		}

		$style_path = GDMU_SITE_DESIGNER_PATH . '/assets/css/godaddy-media-tab.css';
		$style_url  = GDMU_SITE_DESIGNER_URL . '/assets/css/godaddy-media-tab.css';

		if ( ! file_exists( $style_path ) ) {
			$this->log_error( 'CSS file not found', $style_path );
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$style_url,
			array(),
			GDMU_SITE_DESIGNER_VERSION
		);
	}

	/**
	 * Attach configuration as an inline script before the main JS file
	 *
	 * Uses wp_add_inline_script with position 'before' so the config object
	 * is guaranteed to exist when the IIFE in godaddy-media-tab.js executes.
	 *
	 * @return void
	 */
	private function add_inline_config(): void {
		$parent_origin = Iframe_Context_Detector::get_parent_origin();
		$sideload_url  = rest_url( 'wp-site-designer/v1/media/sideload' );
		$nonce         = wp_create_nonce( 'wp_rest' );

		$inline_js = sprintf(
			'window.siteDesignerGodaddyMedia = window.siteDesignerGodaddyMedia || {};'
			. 'Object.assign( window.siteDesignerGodaddyMedia, {'
			. 'sideloadUrl: %s,'
			. 'nonce: %s,'
			. 'parentOrigin: %s'
			. '} );',
			wp_json_encode( $sideload_url ),
			wp_json_encode( $nonce ),
			wp_json_encode( $parent_origin )
		);

		wp_add_inline_script( self::SCRIPT_HANDLE, $inline_js, 'before' );
	}

	/**
	 * Check if current user has permission to upload files
	 *
	 * @return bool True if user is logged in and can upload files.
	 */
	private function user_can_upload(): bool {
		return is_user_logged_in() && current_user_can( 'upload_files' );
	}

	/**
	 * Log error message in debug mode
	 *
	 * @param string $message Error message.
	 * @param string $path    Related file path for context.
	 * @return void
	 */
	private function log_error( string $message, string $path ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'Site Designer GoDaddy Media Tab: %s - Path: %s', $message, $path ) );
		}
	}
}
