<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class AddMediaObjectsToSkuInput extends StoreIdRequestInput
{
    public string $skuId;

    /** @var MediaObject[] */
    public array $mediaObjects;

    /**
     * @param array{
     *     skuId: string,
     *     mediaObjects: MediaObject[],
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
