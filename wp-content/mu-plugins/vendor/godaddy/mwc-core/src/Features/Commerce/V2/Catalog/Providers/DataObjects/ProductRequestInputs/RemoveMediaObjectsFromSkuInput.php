<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class RemoveMediaObjectsFromSkuInput extends StoreIdRequestInput
{
    public string $skuId;

    /** @var string[] */
    public array $mediaObjectIds;

    /**
     * @param array{
     *     skuId: string,
     *     mediaObjectIds: string[],
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
