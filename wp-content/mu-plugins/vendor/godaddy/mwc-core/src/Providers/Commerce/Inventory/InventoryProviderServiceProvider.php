<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\HasLocationsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\InventoryProvider;

class InventoryProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        InventoryProviderContract::class,
        HasLocationsContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(InventoryProviderContract::class, InventoryProvider::class);

        if (CatalogIntegration::shouldUseV2Api()) {
            $this->getContainer()->singleton(HasLocationsContract::class, \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\InventoryProvider::class);
        } else {
            $this->getContainer()->singleton(HasLocationsContract::class, InventoryProvider::class);
        }
    }
}
