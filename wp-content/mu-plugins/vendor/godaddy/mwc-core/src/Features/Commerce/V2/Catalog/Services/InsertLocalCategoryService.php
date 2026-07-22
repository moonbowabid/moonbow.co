<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter;

/**
 * Service class to insert a Commerce-originating category into the local database.
 *
 * This service class is intended to be used with the Commerce Catalog V2 endpoint.
 * The constructor dependencies are customized to use the V2-specific mapping service and category adapter.
 */
class InsertLocalCategoryService extends \GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\InsertLocalCategoryService
{
    public function __construct(ListsMappingService $mappingService, CategoryAdapter $categoryAdapter)
    {
        parent::__construct($mappingService, $categoryAdapter);
    }
}
