<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts\ProvisioningProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ConnectExistingSiteInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\Contracts\ProvisioningServiceContract;

/**
 * Orchestrates provisioning business logic.
 */
class ProvisioningService implements ProvisioningServiceContract
{
    /** @var string WordPress option name for the provisioning context ID */
    protected const CONTEXT_ID_OPTION = 'gd_mwc_connected_commerce_provisioning_context_id';

    /** @var string WordPress option name for the provisioning status */
    protected const STATUS_OPTION = 'gd_mwc_connected_commerce_provisioning_status';

    protected ProvisioningProviderContract $provisioningProvider;

    public function __construct(ProvisioningProviderContract $provisioningProvider)
    {
        $this->provisioningProvider = $provisioningProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function connectExistingSite(ConnectExistingSiteInput $input) : ProvisioningContext
    {
        $provisioningContext = $this->provisioningProvider->provisioning()->connectExistingSite($input);

        update_option(static::CONTEXT_ID_OPTION, $provisioningContext->contextId);
        $this->setProvisioningStatus($provisioningContext->provisioningStatus);

        return $provisioningContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getProvisioningContext(GetProvisioningContextInput $input) : ProvisioningContext
    {
        return $this->provisioningProvider->provisioning()->getProvisioningContext($input);
    }

    /**
     * {@inheritDoc}
     */
    public function getProvisioningContextId() : string
    {
        return TypeHelper::string(get_option(static::CONTEXT_ID_OPTION, ''), '');
    }

    /**
     * {@inheritDoc}
     */
    public function getProvisioningStatus() : string
    {
        return TypeHelper::string(get_option(static::STATUS_OPTION, ''), '');
    }

    /**
     * {@inheritDoc}
     */
    public function setProvisioningStatus(string $status) : void
    {
        update_option(static::STATUS_OPTION, $status);
    }
}
