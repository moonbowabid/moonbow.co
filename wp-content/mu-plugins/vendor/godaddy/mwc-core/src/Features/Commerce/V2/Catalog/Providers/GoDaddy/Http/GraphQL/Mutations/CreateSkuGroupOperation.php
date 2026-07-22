<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuGroupResponseFieldsTrait;

/**
 * GraphQL mutation operation for creating a SKU Group in the v2 API.
 */
class CreateSkuGroupOperation extends AbstractGraphQLOperation
{
    use SkuGroupResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            mutation CreateSkuGroup($input: MutationCreateSkuGroupInput!) {
                createSkuGroup(input: $input) {'.
                    $this->getSkuGroupResponseFields().'
                }
            }';
    }
}
