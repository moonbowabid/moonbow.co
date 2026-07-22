<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\Handlers\CheckProvisioningHandler;

/**
 * Handles the scheduled provisioning check action.
 */
class CheckProvisioningInterceptor extends AbstractInterceptor
{
    /** @var string action name for the scheduled provisioning check */
    public const JOB_NAME = 'mwc_gd_connected_commerce_check_provisioning';

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setHandler([CheckProvisioningHandler::class, 'handle'])
            ->execute();
    }
}
