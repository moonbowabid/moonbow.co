<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuResponseFieldsTrait;

/**
 * GraphQL mutation operation for updating a SKU in the v2 API.
 */
class UpdateSkuOperation extends AbstractGraphQLOperation
{
    use SkuResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            mutation UpdateSku($id: String!, $input: MutationUpdateSkuInput!) {
                updateSku(id: $id, input: $input) {'.
                    $this->getSkuResponseFields().'
                }
            }';
    }
}
