<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL operation for reading inventory count by SKU ID and location ID.
 */
class GetInventoryCountOperation extends AbstractGraphQLOperation
{
    protected $operation = 'query GetInventoryCount($skuId: String!, $locationId: String!, $type: String) {
  inventoryCount(skuId: $skuId, locationId: $locationId, type: $type) {
    id
    quantity
    onHand
    type
    createdAt
    updatedAt
    sku {
      id
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
    }
    location {
      id
    }
  }
}';
}
