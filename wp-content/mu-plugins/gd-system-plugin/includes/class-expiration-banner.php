<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Expiration_Banner {

	use Helpers;

	/**
	 * Feature flag name for the expiration banner.
	 */
	const FEATURE_FLAG_NAME = 'expiration_banner';

	/**
	 * Whether the banner should be displayed.
	 *
	 * @var bool|null
	 */
	private $should_show = null;

	public function __construct() {

		add_action( 'admin_notices', [ $this, 'render_admin_banner' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_tracking_script' ] );

	}

	/**
	 * Render the banner in wp-admin using admin_notices hook.
	 *
	 * @action admin_notices
	 */
	public function render_admin_banner() {

		if ( ! $this->should_display() ) {
			return;
		}

		?>
		<div class="notice wpaas-expiration-banner" style="background: #d63638; border-left-color: #d63638; color: #fff; padding: 12px 20px; margin: 5px 0 15px; font-size: 14px; font-weight: 600;">
			<p style="color: #fff; margin: 0;">
				⚠️ <?php
				printf(
					/* translators: %s: opening and closing anchor tags for the renewal link */
					esc_html__( 'Hosting plan for this site has expired. %1$sRenew now%2$s to avoid service disruption.', 'gd-system-plugin' ),
					'<a id="wpaas-expiration-renew" href="https://host.godaddy.com/hosting" target="_blank" rel="noopener" style="color: #fff; text-decoration: underline;">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php

	}

	/**
	 * Enqueue the tracking script when the banner should be displayed.
	 *
	 * @action admin_enqueue_scripts
	 */
	public function enqueue_tracking_script() {

		if ( ! $this->should_display() ) {
			return;
		}

		wp_enqueue_script(
			'wpaas-expiration-banner-js',
			Plugin::assets_url( "js/expiration-banner.min.js" ),
			[],
			Plugin::version(),
			true
		);

	}

	/**
	 * Determine whether the expiration banner should be displayed.
	 *
	 * Checks:
	 * 1. User must be an Administrator.
	 * 2. Feature flag must be enabled.
	 * 3. Subscription must be expired (SUB_EXPIRATION_DATE in the past).
	 *
	 * @return bool
	 */
	private function should_display() {

		if ( null !== $this->should_show ) {
			return $this->should_show;
		}

		$this->should_show = false;

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! isset( $GLOBALS['wpaas_feature_flag'] ) ||
		     ! $GLOBALS['wpaas_feature_flag']->get_feature_flag_value( self::FEATURE_FLAG_NAME, false ) ) {
			return false;
		}

		if ( ! $this->is_subscription_expired() ) {
			return false;
		}

		$this->should_show = true;

		return true;

	}

	/**
	 * Check if the subscription is expired based on GD config.
	 *
	 * @return bool
	 */
	private function is_subscription_expired() {

		if ( ! defined( 'SUB_EXPIRATION_DATE' ) || empty( SUB_EXPIRATION_DATE ) ) {
			return false;
		}

		$expiration_timestamp = strtotime( SUB_EXPIRATION_DATE );

		if ( false === $expiration_timestamp ) {
			return false;
		}

		return time() > $expiration_timestamp;

	}

}
