<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for reading a location by ID.
 */
class ReadLocationOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query ReadLocation($id: String!) {
  location(id: $id) {
    id
    name
    label
    status
    createdAt
    updatedAt
    address {
      addressLine1
      addressLine2
      addressLine3
      adminArea1
      adminArea2
      adminArea3
      adminArea4
      countryCode
      postalCode
    }
  }
}';
}
