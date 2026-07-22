<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\AbstractSkippedResourcesRepository;

/**
 * Tracks products that could not be mapped to a SkuGroup during V2 product mapping.
 */
class SkippedSkuGroupMappingRepository extends AbstractSkippedResourcesRepository
{
    protected string $resourceType = CommerceResourceTypes::SkuGroup;
}
