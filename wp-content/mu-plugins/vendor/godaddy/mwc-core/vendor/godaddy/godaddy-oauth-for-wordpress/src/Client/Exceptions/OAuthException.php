<?php

namespace GoDaddy\WordPress\OAuth\Client\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * Base exception for all OAuth client errors.
 *
 * This is the foundation exception class that all OAuth-related exceptions
 * extend. It provides error code management for tracking API error codes
 * from MWC API responses.
 */
class OAuthException extends BaseException
{
    /**
     * API error code from MWC API response.
     *
     * @var string|null
     */
    private ?string $errorCode = null;

    /**
     * Set error code from API response.
     *
     * Allows setting the API-specific error code (e.g., 'invalid_grant',
     * 'invalid_client') from the MWC API error response. Returns $this
     * for fluent interface support.
     *
     * @param string $errorCode Error code from API
     * @return $this
     */
    public function setErrorCode(string $errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Get error code from API response.
     *
     * Returns the API-specific error code if one was set, or null if
     * no error code is available.
     *
     * @return string|null
     */
    public function getErrorCode() : ?string
    {
        return $this->errorCode;
    }
}
