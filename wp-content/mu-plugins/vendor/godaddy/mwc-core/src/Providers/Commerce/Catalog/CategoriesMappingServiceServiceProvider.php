<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\CategoriesMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\CategoryMapRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;

/**
 * Service provider for the Categories Mapping Service.
 */
class CategoriesMappingServiceServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        CategoriesMappingServiceContract::class,
        CategoryMapRepositoryContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CategoriesMappingServiceContract::class, CategoriesMappingService::class);

        $categoryMapRepositoryConcrete = CatalogIntegration::shouldUseV2Api()
            ? ListMapRepository::class
            : CategoryMapRepository::class;
        $this->getContainer()->singleton(CategoryMapRepositoryContract::class, $categoryMapRepositoryConcrete);
    }
}
