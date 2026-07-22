<?php
/**
 * CDN Version Override Utility
 *
 * Allows non-production environments to load native UI assets from an
 * arbitrary CDN version path (e.g. a PR branch build) via the
 * ?sdui-version= query parameter, persisted across WP navigation in a
 * cookie. Entirely disabled in production.
 *
 * @package gdcorp-wordpress/site-designer-wp-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless utility for CDN version override resolution and persistence.
 */
class CDN_Version_Override {

	/**
	 * Query parameter name for overriding the Native UI version.
	 */
	public const PARAM_NAME = 'sdui-version';

	/**
	 * Cookie name for persisting the override across WP navigation.
	 */
	public const COOKIE_NAME = 'sdui_version_override';

	/**
	 * Regex for valid version strings: lowercase alphanumeric, dots, hyphens.
	 * Prevents path traversal (no slashes, no ..), XSS, and injection.
	 */
	private const VERSION_REGEX = '/^[a-z0-9][a-z0-9.\-]*$/';

	/**
	 * Resolve the active version override from query param or cookie.
	 *
	 * When ?sdui-version=<version> is present, the value is persisted in a
	 * cookie so it survives normal WP navigation. An empty ?sdui-version=
	 * clears the cookie. Production ignores overrides entirely.
	 *
	 * @param string $env Current environment name.
	 * @return string Validated version string, or empty string if none active.
	 */
	public static function resolve( string $env ): string {
		if ( 'production' === $env ) {
			return '';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only, sanitized param for dev/test CDN version switching.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cookie value is sanitized and regex-validated below.
		$from_param = isset( $_GET[ self::PARAM_NAME ] )
			? sanitize_text_field( wp_unslash( $_GET[ self::PARAM_NAME ] ) )
			: null;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' === $from_param ) {
			static::write_cookie( self::COOKIE_NAME, '', time() - 3600 );
			unset( $_COOKIE[ self::COOKIE_NAME ] );
			return '';
		}

		$override = $from_param
			?? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ?? '' ) );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $override && preg_match( self::VERSION_REGEX, $override ) ) {
			if ( null !== $from_param ) {
				static::write_cookie( self::COOKIE_NAME, $override, time() + DAY_IN_SECONDS );
				$_COOKIE[ self::COOKIE_NAME ] = $override;
			}
			return $override;
		}

		return '';
	}

	/**
	 * Read the active override from the cookie (for display purposes).
	 *
	 * Does NOT check $_GET or write cookies — purely reads what's persisted.
	 *
	 * @return string Validated version string, or empty string if none active.
	 */
	public static function get_active(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized and regex-validated below.
		$value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ?? '' ) );

		if ( $value && preg_match( self::VERSION_REGEX, $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Write a cookie via setcookie(). Extracted for testability.
	 *
	 * @codeCoverageIgnore
	 * @param string $name   Cookie name.
	 * @param string $value  Cookie value.
	 * @param int    $expire Expiration timestamp.
	 * @return void
	 */
	protected static function write_cookie( string $name, string $value, int $expire ): void {
		setcookie( $name, $value, $expire, COOKIEPATH, COOKIE_DOMAIN );
	}
}
