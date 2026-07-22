<?php
/**
 * OAuth Complete Page
 *
 * Serves a minimal HTML page that receives a short-lived, single-use
 * handoff code from the BFF (via redirect query parameter) and exchanges
 * it for the real access token + session ID via a back-channel POST to the
 * BFF /handoff endpoint.  Real tokens never appear in any URL.
 *
 * BFF redirect on success:  {returnUrl}&oauth_status=success&handoff_code={code}
 * BFF redirect on failure:  {returnUrl}&oauth_status=error&reason={reason}
 *
 * The returnUrl must include `apiUrl` and `websiteId` as query parameters so
 * the inline script knows where to send the handoff exchange request.
 *
 * @package gdcorp-wordpress/site-designer-wp-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a hidden WP admin page to complete the OAuth handoff from the BFF.
 */
class OAuth_Complete {

	/**
	 * Plugin configuration.
	 *
	 * @var object
	 */
	private object $config;

	/**
	 * Constructor.
	 *
	 * @param object $config Plugin configuration instance.
	 */
	public function __construct( object $config ) {
		$this->config = $config;
	}

	/**
	 * Initialize the class and register hooks.
	 *
	 * @param object $config Plugin configuration instance.
	 * @return void
	 */
	public static function init( object $config ): void {
		$instance = new self( $config );
		add_action( 'admin_menu', array( $instance, 'register_page' ) );
	}

	/**
	 * Register the hidden admin page.
	 */
	public function register_page(): void {
		$hook = add_submenu_page(
			'',
			'Sign-in complete',
			'Sign-in complete',
			'edit_theme_options',
			'sdui-oauth-complete',
			'__return_false'
		);

		if ( $hook ) {
			add_action( "load-{$hook}", array( $this, 'render_page' ) );
		}
	}

	/**
	 * Render the standalone complete page before WP admin header is output.
	 */
	public function render_page(): void {
		nocache_headers();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Inbound BFF redirect; no nonce is available.
		$oauth_status = isset( $_GET['oauth_status'] ) ? sanitize_text_field( wp_unslash( $_GET['oauth_status'] ) ) : '';
		$handoff_code = isset( $_GET['handoff_code'] ) ? sanitize_text_field( wp_unslash( $_GET['handoff_code'] ) ) : '';
		$error_reason = isset( $_GET['reason'] ) ? sanitize_text_field( wp_unslash( $_GET['reason'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$api_url    = $this->config->get_api_domain() . $this->config->get_api_path_prefix();
		$website_id = defined( 'GD_ACCOUNT_UID' ) ? (string) GD_ACCOUNT_UID : '';

		$params_json = wp_json_encode(
			array(
				'oauthStatus' => $oauth_status,
				'handoffCode' => $handoff_code,
				'errorReason' => $error_reason,
				'apiUrl'      => $api_url,
				'websiteId'   => $website_id,
			)
		);
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
		<head><meta charset="utf-8"><title>Completing sign-in&hellip;</title></head>
		<body>
		<div id="sdui-oauth-data" data-params="<?php echo esc_attr( $params_json ); ?>"></div>
		<script>
		(function () {
			var p = JSON.parse(document.getElementById('sdui-oauth-data').getAttribute('data-params'));

			if (p.oauthStatus !== 'success' || !p.handoffCode || !p.apiUrl || !p.websiteId) {
				window.close();
				return;
			}

			fetch(p.apiUrl + '/oauth/websites/' + encodeURIComponent(p.websiteId) + '/handoff', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ handoffCode: p.handoffCode }),
			})
			.then(function (res) { return res.ok ? res.json() : null; })
			.then(function (data) {
				if (data && data.accessToken && data.sessionId) {
					localStorage.setItem('sdui-oauth-access-token', data.accessToken);
					localStorage.setItem('sdui-oauth-session-id',   data.sessionId);
				}
				window.close();
			})
			.catch(function () { window.close(); });
		})();
		</script>
		</body>
		</html>
		<?php
		exit;
	}
}
