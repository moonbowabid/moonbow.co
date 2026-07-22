<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for querying list tree nodes by their associated list IDs.
 */
class QueryListTreeNodesByListIdsOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query QueryListTreeNodesByListIds($first: Int!, $after: String, $listIds: [String!]!) {
  listTreeNodes(first: $first, after: $after, listId: { in: $listIds }) {
    edges {
      node {
        id
        list {
          id
        }
        listTree {
          id
        }
        parentListTreeNode {
          id
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}';
}
