<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Factories\SkuMappingStrategyFactory;

class SkuMappingService extends AbstractMappingService implements ProductsMappingServiceContract
{
    public function __construct(SkuMappingStrategyFactory $mappingStrategyFactory)
    {
        parent::__construct($mappingStrategyFactory);
    }
}
