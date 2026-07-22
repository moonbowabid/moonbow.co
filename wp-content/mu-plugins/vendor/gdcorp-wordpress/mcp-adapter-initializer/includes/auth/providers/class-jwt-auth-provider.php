<?php
declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Providers;

use GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth\Auth_Provider;
use MCP_JWT_Authenticator;

/**
 * Authentication provider for JWT-based validation.
 *
 * Wraps the existing MCP_JWT_Authenticator class into the
 * Auth_Provider interface, including admin-user resolution on success.
 *
 * @package mcp-adapter-initializer
 * @since 1.6.0
 */
class JWT_Auth_Provider implements Auth_Provider {

	/**
	 * Last authentication error message.
	 *
	 * @var string|null
	 */
	private ?string $error = null;

	/**
	 * JWT authentication handler.
	 *
	 * @var MCP_JWT_Authenticator
	 */
	private MCP_JWT_Authenticator $authenticator;

	/**
	 * Constructor.
	 *
	 * @param MCP_JWT_Authenticator|null $authenticator Optional authenticator instance for testing.
	 */
	public function __construct( ?MCP_JWT_Authenticator $authenticator = null ) {
		$this->authenticator = $authenticator ?? new MCP_JWT_Authenticator();
	}

	/**
	 * Check if a JWT header is present in the current request.
	 *
	 * @return bool True if the X-GD-JWT header exists and is non-empty.
	 */
	public function can_handle(): bool {
		return ! empty( $_SERVER['HTTP_X_GD_JWT'] );
	}

	/**
	 * Validate the JWT and resolve to a WordPress admin user.
	 *
	 * @return int Admin user ID on success, 0 on failure.
	 */
	public function authenticate(): int {
		$this->error = null;

		$jwt     = isset( $_SERVER['HTTP_X_GD_JWT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_GD_JWT'] ) ) : '';
		$site_id = isset( $_SERVER['HTTP_X_GD_SITE_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_GD_SITE_ID'] ) ) : '';

		if ( ! $this->authenticator->authenticate_request( $jwt, $site_id ) ) {
			$this->error = 'JWT authentication failed';
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
		return 20;
	}
}
