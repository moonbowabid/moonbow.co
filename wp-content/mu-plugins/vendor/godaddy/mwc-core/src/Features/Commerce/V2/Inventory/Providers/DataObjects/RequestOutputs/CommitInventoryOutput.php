<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;

/**
 * Data object for the output of committing inventory.
 * {@see CommitInventoryOutput::$inventoryAdjustments} is an array because one single commit operation results in
 * multiple adjustments:
 *  - An increment to COMMITTED
 *  - A decrement to AVAILABLE.
 */
class CommitInventoryOutput extends AbstractDataObject
{
    /** @var InventoryAdjustment[] */
    public array $inventoryAdjustments;

    /**
     * Constructor.
     *
     * @param array{
     *     inventoryAdjustments: array<InventoryAdjustment>
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
