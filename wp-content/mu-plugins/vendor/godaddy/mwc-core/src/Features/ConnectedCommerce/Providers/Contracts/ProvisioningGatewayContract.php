<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ConnectExistingSiteInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;

interface ProvisioningGatewayContract
{
    /**
     * Connects an existing site to Commerce by creating a provisioning context.
     *
     * @param ConnectExistingSiteInput $input
     * @return ProvisioningContext
     * @throws CommerceExceptionContract
     */
    public function connectExistingSite(ConnectExistingSiteInput $input) : ProvisioningContext;

    /**
     * Gets the provisioning context by ID.
     *
     * @param GetProvisioningContextInput $input
     * @return ProvisioningContext
     * @throws CommerceExceptionContract
     */
    public function getProvisioningContext(GetProvisioningContextInput $input) : ProvisioningContext;
}
