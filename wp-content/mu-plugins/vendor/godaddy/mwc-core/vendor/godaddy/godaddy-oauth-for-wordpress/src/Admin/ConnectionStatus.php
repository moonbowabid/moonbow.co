<?php

namespace GoDaddy\WordPress\OAuth\Admin;

use GoDaddy\WordPress\OAuth\Client\Models\AccessToken;

/**
 * Connection Status value object.
 *
 * Wraps an AccessToken and provides convenient methods for checking
 * the current OAuth connection status. This is an immutable value object.
 */
class ConnectionStatus
{
    /**
     * The access token, or null if not connected.
     *
     * @var AccessToken|null
     */
    private ?AccessToken $token;

    /**
     * Whether the connection is in degraded state.
     *
     * @var bool
     */
    private bool $isDegraded;

    /**
     * Constructor.
     *
     * @param AccessToken|null $token The access token or null if not connected
     * @param bool $isDegraded Whether the refresh token is invalid
     */
    public function __construct(?AccessToken $token, bool $isDegraded = false)
    {
        $this->token = $token;
        $this->isDegraded = $isDegraded;
    }

    /**
     * Check if connected (token exists).
     *
     * @return bool True if a token exists
     */
    public function isConnected() : bool
    {
        return $this->token !== null;
    }

    /**
     * Check if token has expired.
     *
     * @return bool True if token exists and has expired
     */
    public function isExpired() : bool
    {
        return $this->token !== null && $this->token->hasExpired();
    }

    /**
     * Check if connection is valid (connected and not expired).
     *
     * @return bool True if connected and not expired
     */
    public function isValid() : bool
    {
        return $this->isConnected() && ! $this->isExpired();
    }

    /**
     * Check if the connection is in degraded state.
     *
     * Degraded state means the refresh token is invalid but the access token
     * may still be valid. The connection will expire when the access token expires.
     *
     * @return bool True if in degraded state
     */
    public function isDegraded() : bool
    {
        return $this->isDegraded;
    }

    /**
     * Get the token scope.
     *
     * @return string The scope string or empty string if not connected
     */
    public function getScope() : string
    {
        return $this->token !== null ? $this->token->getScope() : '';
    }

    /**
     * Get the token expiration timestamp.
     *
     * @return int|null Unix timestamp when token expires, or null if not connected
     */
    public function getExpiresAt() : ?int
    {
        return $this->token !== null ? $this->token->getExpiresAt() : null;
    }

    /**
     * Get the underlying token object.
     *
     * @return AccessToken|null The token or null if not connected
     */
    public function getToken() : ?AccessToken
    {
        return $this->token;
    }
}
