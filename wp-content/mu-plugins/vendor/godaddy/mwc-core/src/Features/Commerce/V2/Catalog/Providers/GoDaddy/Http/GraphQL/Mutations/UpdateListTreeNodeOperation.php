<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for updating a list tree node.
 */
class UpdateListTreeNodeOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation UpdateListTreeNode($id: String!, $input: MutationUpdateListTreeNodeInput!) {
            updateListTreeNode(id: $id, input: $input) {
                id
                parentListTreeNode {
                    id
                }
            }
        }';
}
