<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for reading inventory adjustment by ID.
 */
class ReadInventoryAdjustmentOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query ReadInventoryAdjustment($id: String!) {
  inventoryAdjustment(id: $id) {
    id
    delta
    type
    occurredAt
    sku {
      id
      backorderLimit
    }
    location {
      id
    }
  }
}';
}
