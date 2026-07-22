<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

class ReadInventoryCountInput extends StoreIdRequestInput
{
    public string $skuId;
    public string $locationId;
    /** @var string|null @see {@InventoryCountType} -- omitting this will return all types */
    public ?string $type = null;

    /**
     * Constructor.
     *
     * @param array{
     *     storeId: string,
     *     skuId: string,
     *     locationId: string,
     *     type?: string|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
