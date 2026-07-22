<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for getting a SKU Group from the API by its remote UUID.
 */
class GetSkuGroupInput extends StoreIdRequestInput
{
    /** @var string remote SKU Group UUID */
    public string $skuGroupId;

    /**
     * @param array{
     *     skuGroupId: string,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
