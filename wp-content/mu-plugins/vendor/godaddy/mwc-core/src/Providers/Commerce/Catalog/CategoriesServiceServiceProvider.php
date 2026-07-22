<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\CategoriesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;

class CategoriesServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CategoriesServiceContract::class];

    public function register() : void
    {
        $categoriesServiceConcrete = CatalogIntegration::shouldUseV2Api()
            ? \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\CategoriesService::class
            : CategoriesService::class;

        $this->getContainer()->singleton(CategoriesServiceContract::class, $categoriesServiceConcrete);
    }
}
