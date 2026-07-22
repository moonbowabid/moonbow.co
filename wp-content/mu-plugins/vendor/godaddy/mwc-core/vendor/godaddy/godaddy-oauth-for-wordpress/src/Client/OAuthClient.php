<?php

namespace GoDaddy\WordPress\OAuth\Client;

use Exception;
use GoDaddy\WordPress\OAuth\Client\Exceptions\HttpException;
use GoDaddy\WordPress\OAuth\Client\Http\HttpClient;
use GoDaddy\WordPress\OAuth\Client\Models\AccessToken;
use GoDaddy\WordPress\OAuth\Client\Models\AuthorizationOperation;

/**
 * OAuth Client for GoDaddy OAuth Integration.
 *
 * Provides methods for OAuth 2.0 authorization code flow with PKCE,
 * token exchange, token refresh, and token revocation. Integrates
 * with MWC OAuth API endpoints for secure authentication.
 */
class OAuthClient
{
    /**
     * Base API URL.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * HTTP client for API requests.
     *
     * @var HttpClient
     */
    protected HttpClient $httpClient;

    /**
     * Construct OAuth client.
     *
     * Initializes the OAuth client with the specified base API URL.
     * Creates an instance of HttpClient for making API requests.
     *
     * @param string $baseUrl Base API URL (defaults to MWC production API)
     */
    public function __construct(string $baseUrl = 'https://api.mwc.secureserver.net/v1')
    {
        $this->baseUrl = untrailingslashit($baseUrl);
        $this->httpClient = new HttpClient();
    }

    /**
     * Get base URL.
     *
     * Returns the configured base API URL for the OAuth client.
     *
     * @return string Base API URL
     */
    public function getBaseUrl() : string
    {
        return $this->baseUrl;
    }

    /**
     * Get authorization operation.
     *
     * Generates a new authorization operation with PKCE code verifier,
     * code challenge, state parameter, and authorization URL. Returns
     * an AuthorizationOperation object containing all necessary data
     * for initiating the OAuth authorization flow.
     *
     * @param string $sourceUrl Source URL to redirect back to after authorization
     * @param string $scope OAuth scope (space-separated list of permissions)
     * @return AuthorizationOperation Authorization operation with URL, code verifier, and state
     * @throws Exception
     */
    public function getAuthorizationOperation(string $sourceUrl, string $scope) : AuthorizationOperation
    {
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);
        $state = $this->generateState();
        $url = $this->buildAuthorizationUrl($sourceUrl, $scope, $codeChallenge, $state);

        return new AuthorizationOperation($url, $codeVerifier, $state);
    }

    /**
     * Get access token.
     *
     * Exchanges authorization code and PKCE code verifier for an access token.
     * Makes a POST request to the token endpoint and returns an AccessToken
     * object containing access token, refresh token, and expiration data.
     *
     * @param string $code Authorization code from OAuth callback
     * @param string $codeVerifier PKCE code verifier from authorization operation
     * @return AccessToken Access token object with token data
     * @throws HttpException If HTTP request fails
     */
    public function getAccessToken(string $code, string $codeVerifier) : AccessToken
    {
        // Build token endpoint URL
        $endpoint = $this->baseUrl.'/oauth/commerce/token';

        // Make POST request with code and code_verifier
        $response = $this->httpClient->post($endpoint, [
            'code'          => $code,
            'code_verifier' => $codeVerifier,
        ]);

        // Create and return AccessToken from response
        return AccessToken::fromArray($response);
    }

    /**
     * Refresh access token.
     *
     * Uses refresh token to obtain a new access token. Makes a POST request
     * to the refresh endpoint and returns an AccessToken object with new
     * token data. Implements token rotation where refresh token may be renewed.
     *
     * @param string $refreshToken Refresh token from previous token response
     * @return AccessToken New access token object with updated token data
     * @throws HttpException If HTTP request fails
     */
    public function refreshAccessToken(string $refreshToken) : AccessToken
    {
        $endpoint = $this->baseUrl.'/oauth/commerce/refresh';

        $response = $this->httpClient->post($endpoint, [
            'refresh_token' => $refreshToken,
        ]);

        return AccessToken::fromArray($response);
    }

    /**
     * Revoke token.
     *
     * Revokes an access token or refresh token, invalidating it on the server.
     * Makes a POST request to the revocation endpoint. Does not return any value.
     *
     * @param string $token Token to revoke (access token or refresh token)
     * @return void
     * @throws HttpException If HTTP request fails
     */
    public function revokeToken(string $token) : void
    {
        $endpoint = $this->baseUrl.'/oauth/commerce/revoke';

        $this->httpClient->post($endpoint, [
            'token' => $token,
        ]);
    }

    /**
     * Generate PKCE code verifier.
     *
     * Generates a cryptographically random PKCE code verifier per RFC 7636.
     * Returns a 64-character hexadecimal string (32 random bytes).
     *
     * @return string PKCE code verifier (64 hex characters)
     * @throws Exception
     */
    protected function generateCodeVerifier() : string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate PKCE code challenge.
     *
     * Generates a PKCE code challenge from the code verifier per RFC 7636.
     * Uses SHA-256 hash and base64url encoding (no padding).
     *
     * @param string $codeVerifier PKCE code verifier to hash
     * @return string PKCE code challenge (base64url-encoded SHA-256 hash)
     */
    protected function generateCodeChallenge(string $codeVerifier) : string
    {
        // Generate code_challenge (SHA-256 hash, base64url encoded)
        return rtrim(
            strtr(
                base64_encode(hash('sha256', $codeVerifier, true)),
                '+/',
                '-_'
            ),
            '='
        );
    }

    /**
     * Generate state parameter.
     *
     * Generates a cryptographically random state parameter for CSRF protection.
     * Returns a base64url-encoded string (no padding) from 32 random bytes.
     *
     * @return string State parameter for CSRF protection
     * @throws Exception
     */
    protected function generateState() : string
    {
        // Generate 32 cryptographically secure random bytes
        // Base64url encode (no padding)
        return rtrim(
            strtr(
                base64_encode(random_bytes(32)),
                '+/',
                '-_'
            ),
            '='
        );
    }

    /**
     * Build authorization URL.
     *
     * Constructs the complete authorization URL with all required query parameters.
     * Appends source_url, source_state, scope, and code_challenge to the
     * authorization endpoint.
     *
     * @param string $sourceUrl Source URL to redirect back to
     * @param string $scope OAuth scope (space-separated permissions)
     * @param string $codeChallenge PKCE code challenge
     * @param string $state State parameter for CSRF protection
     * @return string Complete authorization URL with query parameters
     */
    protected function buildAuthorizationUrl(string $sourceUrl, string $scope, string $codeChallenge, string $state) : string
    {
        $endpoint = $this->baseUrl.'/oauth/commerce/authorize';

        // Use http_build_query for proper URL encoding of nested query strings
        $queryString = http_build_query([
            'source_url'     => $sourceUrl,
            'source_state'   => $state,
            'scope'          => $scope,
            'code_challenge' => $codeChallenge,
        ], '', '&', PHP_QUERY_RFC3986);

        return $endpoint.'?'.$queryString;
    }
}
