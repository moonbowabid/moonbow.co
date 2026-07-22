<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments\CreateInventoryAdjustmentRequestAdapter;

/**
 * Data object for the output of creating an inventory adjustment.
 * {@see CreateInventoryAdjustmentRequestAdapter}.
 */
class CreateInventoryAdjustmentOutput extends AbstractDataObject
{
    public InventoryAdjustment $inventoryAdjustment;

    /**
     * Constructor.
     *
     * @param array{
     *     inventoryAdjustment: InventoryAdjustment
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
