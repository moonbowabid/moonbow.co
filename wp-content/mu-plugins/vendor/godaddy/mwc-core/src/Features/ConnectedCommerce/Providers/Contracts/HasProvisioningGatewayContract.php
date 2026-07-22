<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts;

interface HasProvisioningGatewayContract
{
    /**
     * Gets instance of the provisioning gateway.
     *
     * @return ProvisioningGatewayContract
     */
    public function provisioning() : ProvisioningGatewayContract;
}
