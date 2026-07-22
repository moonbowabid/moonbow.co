<?php

namespace GoDaddy\WordPress\OAuth\Client\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Access Token model.
 *
 * Represents an OAuth access token response from the MWC API.
 * Provides methods for accessing token data and checking expiration status.
 * This is an immutable value object with no setters.
 */
class AccessToken
{
    /**
     * The access token string.
     *
     * @var string
     */
    private string $accessToken;

    /**
     * The refresh token string.
     *
     * @var string
     */
    private string $refreshToken;

    /**
     * Token expiration time in seconds.
     *
     * @var int
     */
    private int $expiresIn;

    /**
     * Token type (typically "Bearer").
     *
     * @var string
     */
    private string $tokenType;

    /**
     * Space-separated list of granted scopes.
     *
     * @var string
     */
    private string $scope;

    /**
     * Unix timestamp when token was created.
     *
     * @var int
     */
    private int $createdAt;

    /**
     * Constructor.
     *
     * Creates a new AccessToken instance. The createdAt timestamp is automatically
     * set to the current time unless explicitly provided.
     *
     * @param string $accessToken The access token string
     * @param string $refreshToken The refresh token string
     * @param int $expiresIn Token expiration time in seconds
     * @param string $tokenType Token type (typically "Bearer")
     * @param string $scope Space-separated list of granted scopes
     * @param int|null $createdAt Optional Unix timestamp when token was created (defaults to current time)
     */
    public function __construct(
        string $accessToken,
        string $refreshToken,
        int $expiresIn,
        string $tokenType,
        string $scope,
        ?int $createdAt = null
    ) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
        $this->tokenType = $tokenType;
        $this->scope = $scope;
        $this->createdAt = $createdAt ?? time();
    }

    /**
     * Get access token string.
     *
     * @return string
     */
    public function getAccessToken() : string
    {
        return $this->accessToken;
    }

    /**
     * Get refresh token string.
     *
     * @return string
     */
    public function getRefreshToken() : string
    {
        return $this->refreshToken;
    }

    /**
     * Get token expiration time in seconds.
     *
     * @return int
     */
    public function getExpiresIn() : int
    {
        return $this->expiresIn;
    }

    /**
     * Get token type.
     *
     * @return string
     */
    public function getTokenType() : string
    {
        return $this->tokenType;
    }

    /**
     * Get granted scopes.
     *
     * @return string
     */
    public function getScope() : string
    {
        return $this->scope;
    }

    /**
     * Get token creation timestamp.
     *
     * @return int
     */
    public function getCreatedAt() : int
    {
        return $this->createdAt;
    }

    /**
     * Get token expiration timestamp.
     *
     * Returns the Unix timestamp when this token will expire,
     * calculated as createdAt + expiresIn.
     *
     * @return int Unix timestamp when the token expires
     */
    public function getExpiresAt() : int
    {
        return $this->createdAt + $this->expiresIn;
    }

    /**
     * Check if token has expired.
     *
     * Compares the current time to the expiration time to determine
     * if the token is still valid. Uses timezone-agnostic Unix timestamps.
     *
     * @return bool True if token has expired, false otherwise
     */
    public function hasExpired() : bool
    {
        return time() >= $this->getExpiresAt();
    }

    /**
     * Convert token to array.
     *
     * Returns an associative array representation of the token data,
     * useful for serialization and storage.
     *
     * @return array<string, mixed> Array with keys: access_token, refresh_token, expires_in, token_type, scope, created_at, expires_at
     */
    public function toArray() : array
    {
        return [
            'access_token'  => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in'    => $this->expiresIn,
            'token_type'    => $this->tokenType,
            'scope'         => $this->scope,
            'created_at'    => $this->createdAt,
            'expires_at'    => $this->getExpiresAt(),
        ];
    }

    /**
     * Get the GoDaddy customer ID from the JWT access token.
     *
     * Decodes the JWT payload and extracts the customer UUID from the
     * `sub` claim, which has the format `customer:{uuid}`.
     *
     * @return string|null Customer UUID or null if not available
     */
    public function getCustomerId() : ?string
    {
        $payload = $this->decodeJwtPayload();

        if (! $payload) {
            return null;
        }

        $sub = TypeHelper::stringOrNull($payload['sub'] ?? null);

        if (! $sub || ! StringHelper::startsWith($sub, 'customer:')) {
            return null;
        }

        return StringHelper::after($sub, 'customer:');
    }

    /**
     * Create AccessToken from API response array.
     *
     * Factory method that creates an AccessToken instance from an API
     * response array or stored token data. Handles missing keys with default values:
     * - Strings default to empty string ''
     * - Integers default to 0
     * - token_type defaults to 'Bearer'
     *
     * @param array<string, mixed> $response API response data
     * @return self
     */
    public static function fromArray(array $response) : self
    {
        return new self(
            isset($response['access_token']) && is_string($response['access_token']) ? $response['access_token'] : '',
            isset($response['refresh_token']) && is_string($response['refresh_token']) ? $response['refresh_token'] : '',
            isset($response['expires_in']) && is_numeric($response['expires_in']) ? (int) $response['expires_in'] : 0,
            isset($response['token_type']) && is_string($response['token_type']) ? $response['token_type'] : 'Bearer',
            isset($response['scope']) && is_string($response['scope']) ? $response['scope'] : '',
            isset($response['created_at']) && is_numeric($response['created_at']) ? (int) $response['created_at'] : null
        );
    }

    /**
     * Decode the JWT payload from the access token string.
     *
     * Splits the token on `.`, base64url-decodes the second segment (payload),
     * and returns the decoded associative array. Does not validate the signature.
     *
     * @return array<string, mixed>|null Decoded payload or null on failure
     */
    protected function decodeJwtPayload() : ?array
    {
        // A valid JWT has three dot-separated segments: header, payload, and signature
        $parts = explode('.', $this->accessToken);

        if (count($parts) !== 3) {
            return null;
        }

        // Convert base64url to standard base64 and decode the payload segment
        $decoded = base64_decode(strtr($parts[1], '-_', '+/'), true);

        if ($decoded === false) {
            return null;
        }

        $payload = TypeHelper::arrayOfStringsAsKeys(json_decode($decoded, true));

        return ! empty($payload) ? $payload : null;
    }
}
