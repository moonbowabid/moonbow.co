<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\CanBuildRelationshipUpdateOperationTrait;

/**
 * GraphQL mutation operation for updating SKU relationships in the v2 API.
 *
 * This operation dynamically builds mutations based on the relationship updates provided.
 */
class UpdateSkuRelationshipsOperation extends AbstractGraphQLOperation
{
    use CanBuildRelationshipUpdateOperationTrait;

    public function __construct(RelationshipUpdates $updates)
    {
        $this->updates = $updates;
        $this->operation = $this->buildDynamicOperation();
    }

    /**
     * Build the GraphQL operation dynamically based on available updates.
     *
     * @return string
     */
    protected function buildDynamicOperation() : string
    {
        return $this->buildDynamicRelationshipOperation('Sku', '$skuId', 'UpdateSkuRelationships');
    }
}
