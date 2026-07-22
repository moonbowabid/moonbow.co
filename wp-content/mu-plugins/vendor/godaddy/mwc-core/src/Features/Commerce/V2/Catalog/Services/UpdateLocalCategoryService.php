<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryWpTermAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;

/**
 * Service class to update a Commerce-originating category in the local database.
 *
 * This service class is intended to be used with the Commerce Catalog V2 endpoint.
 * The constructor dependencies are customized to use the V2-specific mapping service and category adapter.
 */
class UpdateLocalCategoryService extends \GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\UpdateLocalCategoryService
{
    public function __construct(ListMapRepository $categoryMapRepository, CategoryWpTermAdapter $categoryWpTermAdapter)
    {
        parent::__construct($categoryMapRepository, $categoryWpTermAdapter);
    }
}
