<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuResponseFieldsTrait;

/**
 * GraphQL query for retrieving a SKU by its UUID in the v2 API.
 */
class GetSkuOperation extends AbstractGraphQLOperation
{
    use SkuResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            query GetSku($id: String!) {
                sku(id: $id) {'.
                    $this->getSkuResponseFields().'
                }
            }';
    }
}
