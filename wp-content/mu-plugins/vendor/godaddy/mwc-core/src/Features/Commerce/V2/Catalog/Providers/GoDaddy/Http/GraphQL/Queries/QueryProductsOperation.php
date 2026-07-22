<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuGroupResponseFieldsTrait;

/**
 * GraphQL operation for querying a paginated page of products (SKU Groups) in the v2 API.
 */
class QueryProductsOperation extends AbstractGraphQLOperation
{
    use SkuGroupResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            query QueryProducts($first: Int!, $after: String, $status: SKUGroupStatusFilter) {
                skuGroups(first: $first, after: $after, status: $status) {
                    edges {
                        node {'.$this->getSkuGroupResponseFields().'}
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }';
    }
}
