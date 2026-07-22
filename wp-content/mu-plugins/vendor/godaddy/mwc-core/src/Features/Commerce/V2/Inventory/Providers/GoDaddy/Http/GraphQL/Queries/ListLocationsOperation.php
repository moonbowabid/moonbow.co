<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for listing locations.
 */
class ListLocationsOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query ListLocations($first: Int) {
  locations(first: $first) {
    edges {
      node {
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
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}';
}
