<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for querying SKUs with their inventory counts.
 */
class ListInventoryCountsOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query GetSkusWithInventoryCounts($skuIds: [String!]!) {
  skus(id: { in: $skuIds }) {
    edges {
      node {
        id
        code
        backorderLimit
        commerceAppsMetafields: metafields(first: 100, namespace: "commerce-apps") {
          edges {
            node {
              namespace
              key
              value
              type
            }
          }
        }
        catalogV1Metafields: metafields(first: 100, namespace: "catalog-v1-product") {
          edges {
            node {
              namespace
              key
              value
              type
            }
          }
        }
        inventoryCounts(first: 100) {
          edges {
            node {
              id
              quantity
              onHand
              type
              createdAt
              updatedAt
              location {
                id
              }
            }
          }
        }
      }
    }
  }
}';
}
