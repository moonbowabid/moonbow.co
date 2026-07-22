<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class UpsertLocationInput extends StoreIdRequestInput
{
    public Location $location;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     location: Location,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
