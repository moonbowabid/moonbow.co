<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for querying lists.
 */
class QueryListsOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query QueryLists($name: NameFilter) {
  lists(name: $name) {
    edges {
      node {
        id
        name
        label
        description
        status
        createdAt
        updatedAt
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}';
}
