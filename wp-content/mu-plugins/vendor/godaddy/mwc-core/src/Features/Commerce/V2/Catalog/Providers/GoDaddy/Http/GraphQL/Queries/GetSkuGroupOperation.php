<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuGroupResponseFieldsTrait;

/**
 * GraphQL query for retrieving a SKU Group by its UUID in the v2 API.
 */
class GetSkuGroupOperation extends AbstractGraphQLOperation
{
    use SkuGroupResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            query GetSkuGroup($id: String!) {
                skuGroup(id: $id) {'.
                    $this->getSkuGroupResponseFields().'
                }
            }';
    }
}
