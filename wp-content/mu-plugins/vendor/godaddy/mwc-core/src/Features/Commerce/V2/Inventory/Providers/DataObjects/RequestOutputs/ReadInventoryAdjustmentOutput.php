<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;

/**
 * Data object for the output of reading an inventory adjustment.
 */
class ReadInventoryAdjustmentOutput extends AbstractDataObject
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
