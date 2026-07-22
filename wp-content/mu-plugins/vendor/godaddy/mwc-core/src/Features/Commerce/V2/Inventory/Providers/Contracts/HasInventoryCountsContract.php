<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts;

/**
 * Contract for providers that have an inventory count gateway.
 */
interface HasInventoryCountsContract
{
    /**
     * Gets the inventory counts gateway.
     *
     * @return InventoryCountsGatewayContract
     */
    public function inventoryCounts() : InventoryCountsGatewayContract;
}
