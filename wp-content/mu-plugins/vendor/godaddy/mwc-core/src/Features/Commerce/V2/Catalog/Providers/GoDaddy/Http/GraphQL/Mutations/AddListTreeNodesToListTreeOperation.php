<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for adding list tree nodes to a list tree.
 */
class AddListTreeNodesToListTreeOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation AddListTreeNodesToListTree($id: String!, $input: MutationAddListTreeNodesToListTreeInput!) {
            addListTreeNodesToListTree(id: $id, input: $input) {
                id
                listTreeNodes {
                    edges {
                        node {
                            id
                            list {
                                id
                            }
                        }
                    }
                }
            }
        }';
}
