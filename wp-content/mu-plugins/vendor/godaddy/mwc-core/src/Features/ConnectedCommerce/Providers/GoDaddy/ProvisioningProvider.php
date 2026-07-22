<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts\ProvisioningGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts\ProvisioningProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy\Gateways\ProvisioningGateway;

/**
 * Provider for provisioning operations.
 */
class ProvisioningProvider implements ProvisioningProviderContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function provisioning() : ProvisioningGatewayContract
    {
        return ProvisioningGateway::getNewInstance();
    }
}
