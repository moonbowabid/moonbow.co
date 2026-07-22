<?php

namespace GoDaddy\WordPress\OAuth\Client\Models;

/**
 * Authorization operation model.
 *
 * Represents an authorization operation containing the authorization URL,
 * PKCE code verifier, and CSRF state token. This is an immutable value object
 * returned by the OAuth client when initiating an authorization flow.
 */
class AuthorizationOperation
{
    /**
     * Authorization URL to redirect user to.
     *
     * @var string
     */
    private string $url;

    /**
     * PKCE code verifier for authorization code exchange.
     *
     * @var string
     */
    private string $codeVerifier;

    /**
     * CSRF state token for validation.
     *
     * @var string
     */
    private string $state;

    /**
     * Timestamp when the operation was created.
     *
     * @var int
     */
    private int $createdAt;

    /**
     * Constructor.
     *
     * @param string $url Authorization URL
     * @param string $codeVerifier PKCE code verifier
     * @param string $state CSRF state token
     * @param int|null $createdAt Timestamp when created (defaults to current time)
     */
    public function __construct(string $url, string $codeVerifier, string $state, ?int $createdAt = null)
    {
        $this->url = $url;
        $this->codeVerifier = $codeVerifier;
        $this->state = $state;
        $this->createdAt = $createdAt ?? time();
    }

    /**
     * Create from array.
     *
     * Creates an AuthorizationOperation instance from an associative array,
     * typically used when deserializing from storage.
     *
     * @param array<string, mixed> $data Array with keys: url, code_verifier, state, created_at
     * @return self
     */
    public static function fromArray(array $data) : self
    {
        $url = $data['url'] ?? '';
        $codeVerifier = $data['code_verifier'] ?? '';
        $state = $data['state'] ?? '';
        $createdAt = $data['created_at'] ?? null;

        return new self(
            is_string($url) ? $url : '',
            is_string($codeVerifier) ? $codeVerifier : '',
            is_string($state) ? $state : '',
            is_int($createdAt) ? $createdAt : null
        );
    }

    /**
     * Get authorization URL.
     *
     * Returns the full authorization URL that the user should be redirected to
     * for authentication and consent.
     *
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Get PKCE code verifier.
     *
     * Returns the code verifier that must be stored securely and used later
     * when exchanging the authorization code for tokens.
     *
     * @return string
     */
    public function getCodeVerifier() : string
    {
        return $this->codeVerifier;
    }

    /**
     * Get CSRF state token.
     *
     * Returns the state token that must be stored securely and validated when
     * the authorization callback is received to prevent CSRF attacks.
     *
     * @return string
     */
    public function getState() : string
    {
        return $this->state;
    }

    /**
     * Get created at timestamp.
     *
     * Returns the timestamp when this authorization operation was created,
     * useful for expiry checks.
     *
     * @return int
     */
    public function getCreatedAt() : int
    {
        return $this->createdAt;
    }

    /**
     * Convert to array.
     *
     * Returns an associative array representation of the authorization operation
     * for serialization or storage.
     *
     * @return array<string, mixed> Array with keys: url, code_verifier, state, created_at
     */
    public function toArray() : array
    {
        return [
            'url'           => $this->url,
            'code_verifier' => $this->codeVerifier,
            'state'         => $this->state,
            'created_at'    => $this->createdAt,
        ];
    }
}
