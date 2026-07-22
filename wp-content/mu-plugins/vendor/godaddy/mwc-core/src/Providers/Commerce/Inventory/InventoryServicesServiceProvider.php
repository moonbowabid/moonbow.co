<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\PrimeInventoryCacheServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsServiceWithCache;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LocationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\PrimeInventoryCacheService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\ProductInventoryCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\ReservationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services\InventoryCountsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services\InventoryCountSummariesService;

class InventoryServicesServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        LevelsServiceContract::class,
        LevelsServiceWithCacheContract::class,
        LocationsServiceContract::class,
        ReservationsServiceContract::class,
        ProductInventoryCachingServiceContract::class,
        SummariesServiceContract::class,
        PrimeInventoryCacheServiceContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(LevelsServiceWithCacheContract::class, LevelsServiceWithCache::class);
        $this->getContainer()->singleton(LocationsServiceContract::class, LocationsService::class);
        $this->getContainer()->singleton(ReservationsServiceContract::class, ReservationsService::class);
        $this->getContainer()->singleton(ProductInventoryCachingServiceContract::class, ProductInventoryCachingService::class);
        $this->getContainer()->singleton(PrimeInventoryCacheServiceContract::class, PrimeInventoryCacheService::class);

        if (CatalogIntegration::shouldUseV2Api()) {
            $this->getContainer()->singleton(LevelsServiceContract::class, InventoryCountsService::class);
            $this->getContainer()->bind(SummariesServiceContract::class, InventoryCountSummariesService::class);
        } else {
            $this->getContainer()->singleton(LevelsServiceContract::class, LevelsService::class);
            $this->getContainer()->bind(SummariesServiceContract::class, SummariesService::class);
        }
    }
}
