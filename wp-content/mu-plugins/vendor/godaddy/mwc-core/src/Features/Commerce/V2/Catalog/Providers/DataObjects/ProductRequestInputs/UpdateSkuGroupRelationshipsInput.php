<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class UpdateSkuGroupRelationshipsInput extends StoreIdRequestInput
{
    public string $skuGroupId;
    public RelationshipUpdates $updates;

    /**
     * @param array{
     *     storeId: string,
     *     skuGroupId: string,
     *     updates: RelationshipUpdates
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
