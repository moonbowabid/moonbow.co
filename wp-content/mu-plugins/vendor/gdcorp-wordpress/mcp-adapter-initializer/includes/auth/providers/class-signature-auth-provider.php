<?php
declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Providers;

use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Auth_Provider;
use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Signature_Auth;

/**
 * Authentication provider for WP Request Signature validation.
 *
 * Wraps the existing Signature_Auth class into the Auth_Provider
 * interface, including admin-user resolution on success.
 *
 * @package mcp-adapter-initializer
 * @since 1.6.0
 */
class Signature_Auth_Provider implements Auth_Provider {

	/**
	 * Last authentication error message.
	 *
	 * @var string|null
	 */
	private ?string $error = null;

	/**
	 * Signature authentication handler.
	 *
	 * @var Signature_Auth
	 */
	private Signature_Auth $signature_auth;

	/**
	 * Constructor.
	 *
	 * @param Signature_Auth|null $signature_auth Optional. Injected for testing.
	 */
	public function __construct( ?Signature_Auth $signature_auth = null ) {
		$this->signature_auth = $signature_auth ?? new Signature_Auth();
	}

	/**
	 * Check if signature headers are present in the current request.
	 *
	 * @return bool True if all required signature headers exist.
	 */
	public function can_handle(): bool {
		return $this->signature_auth->has_signature_headers();
	}

	/**
	 * Validate the signature and resolve to a WordPress admin user.
	 *
	 * @return int Admin user ID on success, 0 on failure.
	 */
	public function authenticate(): int {
		$this->error = null;

		if ( ! $this->signature_auth->authenticate_request() ) {
			$this->error = 'Signature validation failed';
			return 0;
		}

		$admin_users = get_users(
			array(
				'role'    => 'administrator',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => 1,
			)
		);

		if ( empty( $admin_users ) ) {
			$this->error = 'No admin user found';
			return 0;
		}

		$admin_user = current( $admin_users );

		return (int) $admin_user->ID;
	}

	/**
	 * Return the failure reason if authenticate() returned 0.
	 *
	 * @return string|null Error message, or null if no failure occurred.
	 */
	public function get_error(): ?string {
		return $this->error;
	}

	/**
	 * Return this provider's priority (lower = checked first).
	 *
	 * @return int Priority value.
	 */
	public function get_priority(): int {
		return 10;
	}
}
