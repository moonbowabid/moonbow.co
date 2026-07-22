<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LocationMappingService as v1LocationMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Repositories\LocationMapRepository;

class LocationMappingService extends v1LocationMappingService
{
    public function __construct(
        LocationMapRepository $locationMapRepository,
        CommerceContextContract $commerceContext
    ) {
        parent::__construct($locationMapRepository, $commerceContext);
    }
}
