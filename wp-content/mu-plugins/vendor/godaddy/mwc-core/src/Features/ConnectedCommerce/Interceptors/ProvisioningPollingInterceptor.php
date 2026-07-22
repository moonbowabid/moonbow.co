<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\Handlers\ProvisioningPollingHandler;

/**
 * Ensures provisioning polling is scheduled while provisioning is pending.
 */
class ProvisioningPollingInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([ProvisioningPollingHandler::class, 'handle'])
            ->execute();
    }
}
