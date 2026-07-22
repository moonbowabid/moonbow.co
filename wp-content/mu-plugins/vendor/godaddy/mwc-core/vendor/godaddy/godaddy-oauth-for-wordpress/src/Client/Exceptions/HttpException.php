<?php

namespace GoDaddy\WordPress\OAuth\Client\Exceptions;

use Throwable;

/**
 * HTTP Exception for network and HTTP errors.
 *
 * Thrown when HTTP requests fail due to network issues, invalid status codes,
 * or other HTTP-related problems. Stores the HTTP status code for easy access.
 */
class HttpException extends OAuthException
{
    /**
     * HTTP status code.
     *
     * @var int
     */
    private int $statusCode;

    /**
     * Constructor.
     *
     * Creates a new HttpException with the given message, optional previous exception,
     * and status code. The status code is stored as a property and also set as the exception code.
     *
     * @param string $message Error message
     * @param Throwable|null $previous Previous exception for chaining
     * @param int $statusCode HTTP status code (e.g., 404, 500) - defaults to 500
     */
    public function __construct(string $message, ?Throwable $previous = null, int $statusCode = 500)
    {
        parent::__construct($message, $previous);
        $this->statusCode = $statusCode;
        $this->code = $statusCode;
    }

    /**
     * Get HTTP status code.
     *
     * Returns the HTTP status code that caused this exception.
     *
     * @return int HTTP status code
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
}
