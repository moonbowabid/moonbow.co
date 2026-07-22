<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for updating a list tree.
 */
class UpdateListTreeOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation UpdateListTree($id: String!, $input: MutationUpdateListTreeInput!) {
            updateListTree(id: $id, input: $input) {
                id
            }
        }';
}
