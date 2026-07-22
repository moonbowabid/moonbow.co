<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\AbstractSkippedResourcesRepository;

/**
 * Tracks products that could not be mapped to a Sku during V2 product mapping.
 */
class SkippedSkuMappingRepository extends AbstractSkippedResourcesRepository
{
    protected string $resourceType = CommerceResourceTypes::Sku;
}
