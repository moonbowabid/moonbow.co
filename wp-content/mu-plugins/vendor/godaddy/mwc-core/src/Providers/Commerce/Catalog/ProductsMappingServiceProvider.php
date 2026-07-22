<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\HandleLocalProductDeletedContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductParentIdResolverContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\LocalProductDeletedHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductParentIdResolver;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductsMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\ProductMapRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping\SkuMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;

class ProductsMappingServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        ProductsMappingServiceContract::class,
        ProductMapRepositoryContract::class,
        ProductParentIdResolverContract::class,
        HandleLocalProductDeletedContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        if (CatalogIntegration::shouldUseV2Api()) {
            $this->getContainer()->singleton(ProductsMappingServiceContract::class, SkuMappingService::class);
            $this->getContainer()->singleton(ProductMapRepositoryContract::class, SkuMapRepository::class);
            $this->getContainer()->singleton(ProductParentIdResolverContract::class, \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ProductParentIdResolver::class);
            $this->getContainer()->singleton(HandleLocalProductDeletedContract::class, \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\LocalProductDeletedHandler::class);
        } else {
            $this->getContainer()->singleton(ProductsMappingServiceContract::class, ProductsMappingService::class);
            $this->getContainer()->singleton(ProductMapRepositoryContract::class, ProductMapRepository::class);
            $this->getContainer()->singleton(ProductParentIdResolverContract::class, ProductParentIdResolver::class);
            $this->getContainer()->singleton(HandleLocalProductDeletedContract::class, LocalProductDeletedHandler::class);
        }
    }
}
