<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListsMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;

/**
 * Adapter to convert local {@see Term} objects into {@see Category} objects, and vice versa.
 *
 * This instance is for the v2 API, and the constructor arguments are set accordingly to use the v2 service and repository.
 */
class CategoryAdapter extends \GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter
{
    public function __construct(ListsMappingService $categoriesMappingService, ListMapRepository $categoryMapRepository)
    {
        parent::__construct($categoriesMappingService, $categoryMapRepository);
    }
}
