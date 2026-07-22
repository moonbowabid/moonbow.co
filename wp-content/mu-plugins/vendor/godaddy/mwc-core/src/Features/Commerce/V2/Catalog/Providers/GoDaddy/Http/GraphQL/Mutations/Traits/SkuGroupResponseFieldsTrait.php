<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits;

/**
 * Trait providing common SKU Group response fields for GraphQL operations.
 */
trait SkuGroupResponseFieldsTrait
{
    use SkuGroupResponsePrimaryFieldsTrait;
    use SkuResponsePrimaryFieldsTrait;

    /**
     * Gets the standard SKU Group response fields.
     *
     * @return string
     */
    protected function getSkuGroupResponseFields() : string
    {
        return $this->getSkuGroupResponsePrimaryFields().
            'skus {
                edges {
                    node {'.$this->getSkuResponsePrimaryFields().'}
                }
            }';
        // - channels @todo MWC-18580
    }
}
