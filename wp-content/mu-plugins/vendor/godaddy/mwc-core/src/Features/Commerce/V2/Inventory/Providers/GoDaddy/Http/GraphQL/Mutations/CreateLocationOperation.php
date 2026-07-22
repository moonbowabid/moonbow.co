<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for creating a location.
 */
class CreateLocationOperation extends AbstractGraphQLOperation
{
    protected $operation = 'mutation CreateLocation($input: MutationCreateLocationInput!) {
  createLocation(input: $input) {
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
