<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class ReadInventoryAdjustmentInput extends StoreIdRequestInput
{
    public string $id;

    /**
     * Constructor.
     *
     * @param array{
     *     storeId: string,
     *     id: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
