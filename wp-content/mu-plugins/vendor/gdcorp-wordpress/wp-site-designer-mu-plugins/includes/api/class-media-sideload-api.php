<?php
/**
 * Media Sideload REST API
 *
 * Downloads images from allowlisted domains and creates WordPress attachments.
 * Used by the GoDaddy Media Tab to import assets selected in the parent MFE's
 * MediaManager into the WordPress media library. Allowlisted domains include
 * GoDaddy CDN hosts (*.wsimg.com) and stock photo providers (media.gettyimages.com),
 * configured per environment in config/site-designer.json under asset_cdn_origins.
 *
 * Security model:
 * - WordPress nonce auth (same-origin call from WP admin JS)
 * - upload_files capability required
 * - URL allowlist restricts downloads to asset_cdn_origins from config
 * - HTTPS-only scheme validation
 * - Rate limited per user (30 requests / 5 minutes)
 *
 * @package wp-site-designer-mu-plugins
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Api;

use GoDaddy\WordPress\Plugins\SiteDesigner\Utils\Config;
use GoDaddy\WordPress\Plugins\SiteDesigner\Utils\Request_Validator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API endpoint for sideloading media from GoDaddy CDN
 */
class Media_Sideload_Api {

	/**
	 * Configuration instance
	 *
	 * @var Config
	 */
	private Config $config;

	/**
	 * Request validator instance
	 *
	 * @var Request_Validator
	 */
	private Request_Validator $request_validator;

	/**
	 * Constructor
	 *
	 * @param Config            $config Configuration instance.
	 * @param Request_Validator $request_validator Request validator instance.
	 */
	public function __construct( Config $config, Request_Validator $request_validator ) {
		$this->config            = $config;
		$this->request_validator = $request_validator;
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'wp-site-designer/v1',
			'/media/sideload',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_sideload' ),
				'permission_callback' => function ( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Parameter required by WordPress REST API permission_callback signature.
					return $this->check_permissions();
				},
				'args'                => array(
					'url'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'filename' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_file_name',
					),
					'title'    => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'alt_text' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'caption'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Check user permissions and rate limit.
	 *
	 * Combines capability check with rate limiting using the current user ID
	 * as the rate limit identifier. This differs from Site_Designer_Api which
	 * uses external identifiers (site_id, origin, JWT) because the sideload
	 * endpoint is a same-origin call from an authenticated WP user.
	 *
	 * Return type omitted for PHP 7.4 compatibility — union types require PHP 8.0+.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permissions() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'insufficient_permissions',
				'You do not have permission to upload files.',
				array( 'status' => 403 )
			);
		}

		$identifier = (string) get_current_user_id();
		if ( ! $this->request_validator->check_rate_limit_sliding( $identifier, 30, 300 ) ) {
			return new WP_Error(
				'rate_limit_exceeded',
				'Too many requests. Please try again later.',
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Handle sideload request.
	 *
	 * Downloads an image from an allowlisted CDN URL and creates a WordPress attachment.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_sideload( WP_REST_Request $request ) {
		$url      = $request->get_param( 'url' );
		$filename = $request->get_param( 'filename' );
		$title    = $request->get_param( 'title' );
		$alt_text = $request->get_param( 'alt_text' );
		$caption  = $request->get_param( 'caption' );

		if ( empty( $url ) ) {
			return new WP_Error(
				'missing_url',
				'The url parameter is required.',
				array( 'status' => 400 )
			);
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'https:' . $url;
		} elseif ( 0 === strpos( $url, 'http://' ) ) {
			return new WP_Error(
				'url_not_allowed',
				'Only HTTPS URLs are allowed.',
				array( 'status' => 403 )
			);
		}

		$url_validation = $this->validate_url( $url );
		if ( is_wp_error( $url_validation ) ) {
			return $url_validation;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$no_redirects = static function ( $args ) {
			$args['redirection'] = 0;
			return $args;
		};
		add_filter( 'http_request_args', $no_redirects ); // phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_args -- Only disabling redirects, not changing timeout.
		$tmp_file = download_url( $url, 300 );
		remove_filter( 'http_request_args', $no_redirects );
		if ( is_wp_error( $tmp_file ) ) {
			return new WP_Error(
				'download_failed',
				'Failed to download the image.',
				array( 'status' => 500 )
			);
		}

		if ( empty( $filename ) ) {
			$url_path = wp_parse_url( $url, PHP_URL_PATH );
			$filename = basename( is_string( $url_path ) ? $url_path : '' );
		}
		if ( empty( $filename ) ) {
			$filename = 'sideloaded-' . time() . '.jpg';
		}

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp_file,
		);

		$attachment_id = media_handle_sideload( $file_array, 0, $title );

		if ( is_wp_error( $attachment_id ) ) {
			// On failure, media_handle_sideload leaves the temp file in place.
			// On success it moves the file to wp-content/uploads, so no cleanup is needed there.
			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}
			return new WP_Error(
				'sideload_failed',
				'Failed to create the attachment.',
				array( 'status' => 500 )
			);
		}

		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		if ( ! empty( $caption ) ) {
			wp_update_post(
				array(
					'ID'           => $attachment_id,
					'post_excerpt' => $caption,
				)
			);
		}

		$attachment_data = wp_prepare_attachment_for_js( $attachment_id );

		return rest_ensure_response( $attachment_data );
	}

	/**
	 * Validate that a URL is allowed for sideloading.
	 *
	 * Checks HTTPS scheme and host against the asset_cdn_origins allowlist.
	 * Supports wildcard entries (e.g., "*.wsimg.com") for suffix matching.
	 *
	 * @param string $url The URL to validate.
	 *
	 * @return true|WP_Error True if valid, WP_Error if not.
	 */
	public function validate_url( string $url ) {
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( 'https' !== $scheme ) {
			return new WP_Error(
				'url_not_allowed',
				'Only HTTPS URLs are allowed.',
				array( 'status' => 403 )
			);
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( empty( $host ) ) {
			return new WP_Error(
				'url_not_allowed',
				'Invalid URL.',
				array( 'status' => 403 )
			);
		}

		$port = wp_parse_url( $url, PHP_URL_PORT );
		if ( null !== $port && 443 !== $port ) {
			return new WP_Error(
				'url_not_allowed',
				'Only standard HTTPS port (443) is allowed.',
				array( 'status' => 403 )
			);
		}

		$allowed_origins = $this->config->get_asset_cdn_origins();
		$host_allowed    = false;

		foreach ( $allowed_origins as $origin ) {
			if ( $this->host_matches_origin( $host, $origin ) ) {
				$host_allowed = true;
				break;
			}
		}

		if ( ! $host_allowed ) {
			return new WP_Error(
				'url_not_allowed',
				'The URL domain is not in the allowlist.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if a host matches an allowed origin.
	 *
	 * Supports two formats:
	 * - Exact match: "https://blobby.wsimg.com" matches host "blobby.wsimg.com"
	 * - Wildcard suffix: "*.wsimg.com" matches any subdomain of wsimg.com
	 *   (e.g., "img1.wsimg.com", "blobby.wsimg.com") but not "wsimg.com" itself
	 *   or "evil-wsimg.com".
	 *
	 * @param string $host   The URL host to check.
	 * @param string $origin The allowed origin (URL or wildcard pattern).
	 *
	 * @return bool True if the host matches the origin.
	 */
	private function host_matches_origin( string $host, string $origin ): bool {
		$origin = strtolower( $origin );

		if ( 0 === strpos( $origin, '*.' ) ) {
			$suffix = substr( $origin, 1 );
			return strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix;
		}

		$origin_host = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );
		return $host === $origin_host;
	}
}
