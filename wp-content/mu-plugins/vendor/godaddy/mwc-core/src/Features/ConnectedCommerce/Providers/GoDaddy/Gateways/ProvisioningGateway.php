<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts\ProvisioningGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ConnectExistingSiteInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy\Adapters\ConnectExistingSiteRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy\Adapters\GetProvisioningContextRequestAdapter;

/**
 * Gateway for provisioning API operations.
 */
class ProvisioningGateway extends AbstractGateway implements ProvisioningGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * {@inheritDoc}
     */
    public function connectExistingSite(ConnectExistingSiteInput $input) : ProvisioningContext
    {
        /** @var ProvisioningContext $result */
        $result = $this->doAdaptedRequest(ConnectExistingSiteRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getProvisioningContext(GetProvisioningContextInput $input) : ProvisioningContext
    {
        /** @var ProvisioningContext $result */
        $result = $this->doAdaptedRequest(GetProvisioningContextRequestAdapter::getNewInstance($input));

        return $result;
    }
}
