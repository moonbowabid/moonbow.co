<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for creating an inventory adjustment.
 */
class CreateInventoryAdjustmentOperation extends AbstractGraphQLOperation
{
    protected $operation = 'mutation CreateInventoryAdjustment($input: CreateInventoryAdjustmentInput!) {
  createInventoryAdjustment(input: $input) {
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
  }
}';
}
