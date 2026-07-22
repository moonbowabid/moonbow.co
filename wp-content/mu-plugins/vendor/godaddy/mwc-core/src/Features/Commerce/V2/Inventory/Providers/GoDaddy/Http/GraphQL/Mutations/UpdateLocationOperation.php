<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for updating a location.
 */
class UpdateLocationOperation extends AbstractGraphQLOperation
{
    protected $operation = 'mutation UpdateLocation($id: String!, $input: MutationUpdateLocationInput!) {
  updateLocation(id: $id, input: $input) {
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
