<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;

/**
 * List inventory count output.
 */
class ListInventoryCountOutput extends AbstractDataObject
{
    /** @var array<string, InventoryCount[]> Inventory counts grouped by SKU ID */
    public array $groupedInventoryCounts;

    /**
     * @param array{
     *     groupedInventoryCounts: array<string, InventoryCount[]>
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
