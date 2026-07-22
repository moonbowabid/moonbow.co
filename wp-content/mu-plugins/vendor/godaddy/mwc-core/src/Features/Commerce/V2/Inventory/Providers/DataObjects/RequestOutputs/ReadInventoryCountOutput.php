<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;

class ReadInventoryCountOutput extends AbstractDataObject
{
    /** @var InventoryCount[]|null this is nullable because the API could actually return no data at all, indicating there's no inventory count set */
    public ?array $inventoryCounts = null;

    /**
     * Constructor.
     *
     * @param array{
     *     inventoryCounts?: InventoryCount[]|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
