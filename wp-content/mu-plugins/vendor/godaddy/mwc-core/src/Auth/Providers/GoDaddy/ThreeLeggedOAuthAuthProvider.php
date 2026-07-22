<?php

namespace GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthCredentialsContract;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Auth\Methods\TokenAuthMethod;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\Models\Token;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;
use GoDaddy\WordPress\OAuth\Client\Exceptions\TokenRefreshException;
use GoDaddy\WordPress\OAuth\Client\Models\AccessToken;
use GoDaddy\WordPress\OAuth\Services\TokenService;

/**
 * Auth provider that attaches a GoDaddy 3-Legged OAuth Bearer token to outgoing HTTP requests.
 */
class ThreeLeggedOAuthAuthProvider implements ThreeLeggedOAuthTokenProviderContract
{
    /** @var TokenService */
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod() : AuthMethodContract
    {
        $accessToken = $this->getValidToken();

        return (new TokenAuthMethod())
            ->setToken($accessToken->getAccessToken())
            ->setType($accessToken->getTokenType());
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials() : AuthCredentialsContract
    {
        $accessToken = $this->getValidToken();

        return (new Token())
            ->setAccessToken($accessToken->getAccessToken())
            ->setTokenType($accessToken->getTokenType())
            ->setScope($accessToken->getScope())
            ->setExpiration($accessToken->getExpiresAt());
    }

    /**
     * Gets a valid access token from the OAuth package.
     *
     * @throws CredentialsCreateFailedException
     */
    protected function getValidToken() : AccessToken
    {
        try {
            return $this->tokenService->getValidToken();
        } catch (TokenRefreshException $e) {
            throw new CredentialsCreateFailedException($e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteCredentials() : void
    {
        // no-op: this provider does not cache credentials
    }
}
