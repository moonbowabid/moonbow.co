<?php

namespace GoDaddy\WordPress\OAuth\Client\Exceptions;

/**
 * Exception thrown when token refresh fails.
 *
 * Thrown by TokenService::getValidToken() when:
 * - No token is available (user hasn't connected)
 * - Refresh token is invalid/expired (permanent failure - tokens cleared)
 * - Network/server error during refresh (temporary failure - tokens preserved)
 * - Lock is held by another process (temporary failure - retry later)
 */
class TokenRefreshException extends OAuthException
{
}
