<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for creating a list in the v2 API.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class CreateListInput extends StoreIdRequestInput
{
    public ListObject $list;

    /**
     * Constructor.
     *
     * @param array{
     *     list: ListObject,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
