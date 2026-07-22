<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Contracts\ListsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Factories\ListsMappingStrategyFactory;

/**
 * Service class to handle mapping of local product category IDs to remote list IDs in V2 API.
 */
class ListsMappingService extends AbstractMappingService implements ListsMappingServiceContract
{
    public function __construct(ListsMappingStrategyFactory $listsMappingStrategyFactory)
    {
        parent::__construct($listsMappingStrategyFactory);
    }
}
