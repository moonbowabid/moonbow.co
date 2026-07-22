<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\ConnectedCommerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Contracts\ProvisioningProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy\ProvisioningProvider;

class ProvisioningProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ProvisioningProviderContract::class];

    public function register() : void
    {
        $this->getContainer()->singleton(ProvisioningProviderContract::class, ProvisioningProvider::class);
    }
}
