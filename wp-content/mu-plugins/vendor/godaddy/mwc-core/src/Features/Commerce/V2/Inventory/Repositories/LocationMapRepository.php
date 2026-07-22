<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Repositories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LocationMapRepository as v1LocationMapRepository;

class LocationMapRepository extends v1LocationMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::Location;
}
