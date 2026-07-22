<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductsService;

class ProductsServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ProductsServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $productsServiceConcrete = CatalogIntegration::shouldUseV2Api()
            ? \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ProductsService::class
            : ProductsService::class;

        $this->getContainer()->singleton(ProductsServiceContract::class, $productsServiceConcrete);
    }
}
