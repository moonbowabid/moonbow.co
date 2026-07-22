<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class QueryListsInput extends StoreIdRequestInput
{
    /**
     * Filter by lists using this name.
     *
     * @var ?string
     */
    public ?string $name = null;

    /**
     * @param array{
     *      name?: ?string,
     *      storeId: string
     *  } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
