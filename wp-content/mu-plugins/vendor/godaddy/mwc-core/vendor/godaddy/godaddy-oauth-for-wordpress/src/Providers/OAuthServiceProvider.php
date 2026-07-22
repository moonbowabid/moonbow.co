<?php

namespace GoDaddy\WordPress\OAuth\Providers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\OAuth\Client\OAuthClient;
use GoDaddy\WordPress\OAuth\Services\TokenService;
use GoDaddy\WordPress\OAuth\Storage\Contracts\AuthorizationOperationRepositoryContract;
use GoDaddy\WordPress\OAuth\Storage\Contracts\TokenRepositoryContract;
use GoDaddy\WordPress\OAuth\Storage\WpOptionsAuthorizationOperationRepository;
use GoDaddy\WordPress\OAuth\Storage\WpOptionsTokenRepository;

/**
 * Service provider for OAuth dependencies.
 *
 * Registers all core bindings needed by the OAuth plugin.
 * Centralizes configuration (base URL) in one place.
 */
class OAuthServiceProvider extends AbstractServiceProvider
{
    /** @var string Default OAuth base URL. */
    public const DEFAULT_BASE_URL = 'https://api.mwc.secureserver.net/v1';

    /** @var string[] */
    protected array $provides = [
        OAuthClient::class,
        TokenRepositoryContract::class,
        AuthorizationOperationRepositoryContract::class,
        TokenService::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(OAuthClient::class, function () {
            return new OAuthClient($this->getBaseUrl());
        });

        $this->getContainer()->bind(
            TokenRepositoryContract::class,
            WpOptionsTokenRepository::class
        );

        $this->getContainer()->bind(
            AuthorizationOperationRepositoryContract::class,
            WpOptionsAuthorizationOperationRepository::class
        );

        $this->getContainer()->singleton(TokenService::class, function () {
            return new TokenService(
                $this->getContainer()->get(OAuthClient::class),
                $this->getContainer()->get(TokenRepositoryContract::class)
            );
        });
    }

    /**
     * Get the OAuth base URL from constant or default.
     *
     * @return string
     */
    protected function getBaseUrl() : string
    {
        if (defined('GODADDY_OAUTH_BASE_URL') && is_string(GODADDY_OAUTH_BASE_URL)) {
            return GODADDY_OAUTH_BASE_URL;
        }

        return self::DEFAULT_BASE_URL;
    }
}
