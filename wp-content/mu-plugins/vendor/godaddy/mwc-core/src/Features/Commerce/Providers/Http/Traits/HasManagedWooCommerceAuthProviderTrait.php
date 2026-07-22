<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits;

use GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;

trait HasManagedWooCommerceAuthProviderTrait
{
    /**
     * Gets the authentication method from the authentication provider.
     *
     * @return AuthMethodContract
     * @throws AuthProviderException|CredentialsCreateFailedException
     */
    protected function getAuthMethodFromAuthProvider() : AuthMethodContract
    {
        try {
            return ContainerFactory::getInstance()
                ->getSharedContainer()
                ->get(ThreeLeggedOAuthTokenProviderContract::class)
                ->getMethod();
        } catch(AuthProviderException|CredentialsCreateFailedException|EntryNotFoundException|ContainerException $e) {
            return AuthProviderFactory::getNewInstance()->getManagedWooCommerceAuthProvider()->getMethod();
        }
    }
}
