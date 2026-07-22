<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CommitInventoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CreateInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\CommitInventoryOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\CreateInventoryAdjustmentOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryAdjustmentOutput;

/**
 * Contract for inventory adjustment gateways.
 */
interface InventoryAdjustmentsGatewayContract
{
    /**
     * Reads a single inventory adjustment by ID.
     *
     * @throws CommerceExceptionContract
     */
    public function read(ReadInventoryAdjustmentInput $input) : ReadInventoryAdjustmentOutput;

    /**
     * Creates an inventory adjustment.
     *
     * @throws CommerceExceptionContract
     */
    public function create(CreateInventoryAdjustmentInput $input) : CreateInventoryAdjustmentOutput;

    /**
     * Commits inventory to an order.
     * This is similar to creating an adjustment, but tailored to reserving inventory for an order.
     *
     * @throws CommerceExceptionContract
     */
    public function commitForOrder(CommitInventoryInput $input) : CommitInventoryOutput;
}
