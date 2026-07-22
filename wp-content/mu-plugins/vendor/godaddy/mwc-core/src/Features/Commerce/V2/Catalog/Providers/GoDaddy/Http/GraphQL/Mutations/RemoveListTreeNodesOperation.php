<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for removing list tree nodes from a list tree.
 */
class RemoveListTreeNodesOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation RemoveListTreeNodesFromListTree($id: String!, $input: MutationRemoveListTreeNodesFromListTreeInput!) {
            removeListTreeNodesFromListTree(id: $id, input: $input) {
                id
            }
        }';
}
