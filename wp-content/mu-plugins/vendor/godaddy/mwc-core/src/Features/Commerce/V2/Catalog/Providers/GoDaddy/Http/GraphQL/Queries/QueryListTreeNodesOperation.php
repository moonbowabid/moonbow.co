<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL query for fetching ListTreeNodes by list ID, including the ancestor chain.
 */
class QueryListTreeNodesOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query QueryListTreeNodes($listId: ListIdFilter) {
  listTreeNodes(listId: $listId, first: 1) {
    edges {
      node {
        id
        list { id name label description htmlDescription status createdAt updatedAt }
        parentListTreeNode {
          id
          list { id name label description htmlDescription status createdAt updatedAt }
          parentListTreeNode {
            id
            list { id name label description htmlDescription status createdAt updatedAt }
            parentListTreeNode {
              id
              list { id name label description htmlDescription status createdAt updatedAt }
              parentListTreeNode {
                id
                list { id name label description htmlDescription status createdAt updatedAt }
                parentListTreeNode {
                  id
                  list { id name label description htmlDescription status createdAt updatedAt }
                }
              }
            }
          }
        }
      }
    }
  }
}';
}
