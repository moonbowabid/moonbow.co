<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuResponseFieldsTrait;

/**
 * GraphQL mutation operation for creating a SKU in the v2 API.
 */
class CreateSkuOperation extends AbstractGraphQLOperation
{
    use SkuResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            mutation CreateSku($input: CreateSKUInput!) {
                createSku(input: $input) {'.
                    $this->getSkuResponseFields().'
                }
            }';
    }
}
