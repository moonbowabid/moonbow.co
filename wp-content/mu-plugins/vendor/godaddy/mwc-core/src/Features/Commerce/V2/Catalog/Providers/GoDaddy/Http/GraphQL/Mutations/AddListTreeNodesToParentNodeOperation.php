<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for adding list tree nodes to a parent node.
 */
class AddListTreeNodesToParentNodeOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation AddListTreeNodesToParentListTreeNode($id: String!, $input: MutationAddListTreeNodesToParentListTreeNodeInput!) {
            addListTreeNodesToParentListTreeNode(id: $id, input: $input) {
                id
                listTreeNodes(first: 100, orderBy: {createdAt: DESC}) {
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
