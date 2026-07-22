<?php
/**
 * Rate Limiter Utility
 *
 * Provides fixed-window rate limiting using WordPress transients.
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixed-window rate limiter backed by WP transients.
 *
 * Note: The get-then-set counter increment is not atomic. Under high
 * concurrency with a persistent object cache (Redis/Memcached), two
 * requests may read the same count and both increment to count+1
 * instead of count+2 (TOCTOU race). This means the limiter may allow
 * slightly more requests than $max_requests under load. For the
 * current use case (per-user REST rate limiting) this is acceptable.
 * A stricter implementation would use atomic operations (e.g. Redis
 * INCR via wp_cache_incr) but that requires an object cache backend.
 */
class Rate_Limiter {

	/**
	 * Check whether a request is within the rate limit.
	 *
	 * Uses a fixed-window counter keyed by identifier + time bucket.
	 *
	 * @param string $identifier    Unique key (e.g., 'palette_123' for user ID 123).
	 * @param int    $max_requests  Maximum requests allowed within the window.
	 * @param int    $window_seconds Time window in seconds.
	 *
	 * @return bool True if request is within limit (allowed), false if rate limited.
	 */
	public static function check( string $identifier, int $max_requests = 10, int $window_seconds = 60 ): bool {
		$now        = time();
		$window_key = (int) floor( $now / $window_seconds );

		$key   = 'wp_site_designer_ui_rl_' . hash( 'sha256', $identifier . '_' . $window_key );
		$count = (int) get_transient( $key );

		if ( $count >= $max_requests ) {
			return false;
		}

		set_transient( $key, $count + 1, $window_seconds * 2 );

		return true;
	}
}
