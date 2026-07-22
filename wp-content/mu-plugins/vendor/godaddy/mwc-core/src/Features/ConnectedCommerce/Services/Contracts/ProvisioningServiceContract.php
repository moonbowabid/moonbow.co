<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ConnectExistingSiteInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;

interface ProvisioningServiceContract
{
    /**
     * Connects an existing site to Commerce by creating a provisioning context.
     *
     * @throws CommerceExceptionContract
     */
    public function connectExistingSite(ConnectExistingSiteInput $input) : ProvisioningContext;

    /**
     * Gets the provisioning context by ID.
     *
     * @throws CommerceExceptionContract
     */
    public function getProvisioningContext(GetProvisioningContextInput $input) : ProvisioningContext;

    /**
     * Gets the stored provisioning context ID.
     */
    public function getProvisioningContextId() : string;

    /**
     * Gets the current provisioning status.
     */
    public function getProvisioningStatus() : string;

    /**
     * Sets the provisioning status.
     */
    public function setProvisioningStatus(string $status) : void;
}
