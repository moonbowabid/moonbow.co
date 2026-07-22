<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class UpdateSkuRelationshipsInput extends StoreIdRequestInput
{
    public string $skuId;
    public RelationshipUpdates $updates;

    /**
     * @param array{
     *     storeId: string,
     *     skuId: string,
     *     updates: RelationshipUpdates
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
