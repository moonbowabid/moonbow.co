<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for committing inventory for an order.
 */
class CommitInventoryOperation extends AbstractGraphQLOperation
{
    protected $operation = 'mutation CommitInventoryForOrder($input: MutationCommitInventoryInput!) {
  commitInventory(input: $input) {
    id
    delta
    type
    occurredAt
    sku {
      id
      code
      backorderLimit
    }
    location {
      id
    }
    references {
      edges {
        node {
          id
          origin
          value
        }
      }
    }
  }
}';
}
