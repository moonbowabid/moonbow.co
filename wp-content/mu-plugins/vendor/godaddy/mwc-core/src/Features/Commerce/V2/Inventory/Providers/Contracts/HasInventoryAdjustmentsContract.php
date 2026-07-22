<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts;

/**
 * Contract for providers that have an inventory adjustments gateway.
 */
interface HasInventoryAdjustmentsContract
{
    /**
     * Gets the inventory adjustments gateway.
     *
     * @return InventoryAdjustmentsGatewayContract
     */
    public function adjustments() : InventoryAdjustmentsGatewayContract;
}
