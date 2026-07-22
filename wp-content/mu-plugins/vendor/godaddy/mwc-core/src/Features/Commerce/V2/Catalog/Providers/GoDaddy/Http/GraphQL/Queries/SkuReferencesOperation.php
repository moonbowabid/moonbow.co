<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuGroupResponsePrimaryFieldsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuResponsePrimaryFieldsTrait;

/**
 * Query retrieves all v1 references required for mapping v1 <-> v2 product data.
 */
class SkuReferencesOperation extends AbstractGraphQLOperation
{
    use SkuResponsePrimaryFieldsTrait;
    use SkuGroupResponsePrimaryFieldsTrait;

    public function __construct()
    {
        $this->operation = 'query GetSkusDetailsAndReferences(
            $first: Int!
            $after: String
            $referenceValues: [String!]!
            $status: SKUStatusFilter = {in: ["ACTIVE", "DRAFT"]}
        ) {
      skus(
        first: $first
        after: $after
        referenceValue: {
          in: $referenceValues
        },
        status: $status
      ) {
        edges {
          node {'
            .$this->getSkuResponsePrimaryFields().'
            references {
              edges {
                node {
                  id
                  origin
                  value
                }
              }
            }
            skuGroup {'
              .$this->getSkuGroupResponsePrimaryFields().'references {
                edges {
                  node {
                    id
                    origin
                    value
                  }
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
}
