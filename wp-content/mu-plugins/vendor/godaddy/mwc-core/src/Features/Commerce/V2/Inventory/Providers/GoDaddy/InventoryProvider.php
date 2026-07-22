<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\HasLocationsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\LocationsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts\HasInventoryAdjustmentsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts\HasInventoryCountsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts\InventoryAdjustmentsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts\InventoryCountsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Gateways\InventoryAdjustmentsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Gateways\InventoryCountsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Gateways\LocationsGateway;

/**
 * The GoDaddy v2 inventory provider.
 * For v1, {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\InventoryProvider}.
 */
class InventoryProvider implements HasLocationsContract, HasInventoryCountsContract, HasInventoryAdjustmentsContract
{
    /**
     * {@inheritDoc}
     */
    public function locations() : LocationsGatewayContract
    {
        return LocationsGateway::getNewInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function inventoryCounts() : InventoryCountsGatewayContract
    {
        return InventoryCountsGateway::getNewInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function adjustments() : InventoryAdjustmentsGatewayContract
    {
        return InventoryAdjustmentsGateway::getNewInstance();
    }
}
