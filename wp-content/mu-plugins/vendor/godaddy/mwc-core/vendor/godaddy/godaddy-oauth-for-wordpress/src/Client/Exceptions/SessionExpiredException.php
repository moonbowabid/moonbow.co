<?php

namespace GoDaddy\WordPress\OAuth\Client\Exceptions;

/**
 * Exception thrown when the OAuth session has permanently expired.
 *
 * Thrown when the refresh token is invalid, expired, or revoked
 * (e.g., invalid_grant, invalid_token error codes). When this exception
 * is thrown, stored tokens have been deleted and the user must reconnect.
 *
 * Callers can catch this specifically to distinguish permanent session
 * expiry from temporary refresh failures (TokenRefreshException).
 */
class SessionExpiredException extends TokenRefreshException
{
}
