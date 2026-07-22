<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class ReadLocationInput extends StoreIdRequestInput
{
    public string $locationId;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     locationId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
