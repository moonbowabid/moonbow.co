<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * Query retrieves v1 location reference to enable mapping v2 locations.
 */
class LocationsReferencesOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query GetLocations($first: Int!, $after: String, $referenceValues: [String!]!) {
  locations(
    first: $first
    after: $after
    referenceValue: {
      in: $referenceValues
    }
  ) {
    edges {
      node {
        id
        name
        label
        status
        references {
          edges {
            node {
              origin
              id
              value
            }
          }
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
