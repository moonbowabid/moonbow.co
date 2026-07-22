<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for getting a SKU from the API by remote UUID.
 */
class GetSkuInput extends StoreIdRequestInput
{
    /** @var string remote sku UUID */
    public string $skuId;

    /**
     * @param array{
     *     skuId: string,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
