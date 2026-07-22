<?php
/**
 * WP Extensions Bootstrap
 *
 * Package entry point. Loads translations; extension initialisation
 * is the consumer's responsibility.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap — loads shared package infrastructure (translations).
 */
class Bootstrap {

	/**
	 * Load package translations.
	 *
	 * Safe to call multiple times; runs only on the first invocation.
	 *
	 * @return void
	 */
	public static function init(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;

		\load_textdomain(
			'wp-site-designer-wp-extensions',
			__DIR__ . '/languages/wp-site-designer-wp-extensions-' . \determine_locale() . '.mo'
		);
	}
}
