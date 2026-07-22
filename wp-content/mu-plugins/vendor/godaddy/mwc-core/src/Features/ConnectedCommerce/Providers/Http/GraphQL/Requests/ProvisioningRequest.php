<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Requests;

use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;

/**
 * Sends provisioning GraphQL operations to the MWC API.
 */
class ProvisioningRequest extends Request
{
    use CanGetNewInstanceTrait;

    /**
     * Creates an authenticated instance.
     *
     * @return static
     * @throws CredentialsCreateFailedException
     * @throws ContainerException
     */
    public static function withAuth(GraphQLOperationContract $operation)
    {
        return static::getNewInstance($operation)
            ->setUrl(StringHelper::beforeLast(ManagedWooCommerceRepository::getApiUrl(), '/').'/graphql')
            ->setAuthMethod(
                ContainerFactory::getInstance()->getSharedContainer()
                    ->get(ThreeLeggedOAuthTokenProviderContract::class)
                    ->getMethod()
            );
    }
}
