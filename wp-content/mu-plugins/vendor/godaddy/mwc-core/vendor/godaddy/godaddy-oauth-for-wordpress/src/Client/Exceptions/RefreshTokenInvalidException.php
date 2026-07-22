<?php

namespace GoDaddy\WordPress\OAuth\Client\Exceptions;

/**
 * Exception thrown when the refresh token is invalid but access token is still valid.
 *
 * Thrown when the refresh token is invalid, expired, or revoked
 * (e.g., invalid_grant, invalid_token error codes) but the access token
 * has not yet expired. When this exception is thrown, tokens are preserved
 * and the connection enters a degraded state where the access token can
 * still be used until it expires.
 *
 * Callers can catch this specifically to distinguish from:
 * - SessionExpiredException: permanent failure, tokens deleted
 * - TokenRefreshException: temporary failure, can retry
 */
class RefreshTokenInvalidException extends TokenRefreshException
{
}
