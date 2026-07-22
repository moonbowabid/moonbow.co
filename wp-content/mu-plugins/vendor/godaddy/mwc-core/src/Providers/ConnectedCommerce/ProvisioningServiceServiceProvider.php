<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\ConnectedCommerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\Contracts\ProvisioningServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\ProvisioningService;

class ProvisioningServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ProvisioningServiceContract::class];

    public function register() : void
    {
        $this->getContainer()->singleton(ProvisioningServiceContract::class, ProvisioningService::class);
    }
}
