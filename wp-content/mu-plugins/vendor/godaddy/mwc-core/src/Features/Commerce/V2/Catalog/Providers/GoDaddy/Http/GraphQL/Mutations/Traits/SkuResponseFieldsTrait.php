<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits;

/**
 * Trait providing common SKU response fields for GraphQL operations.
 */
trait SkuResponseFieldsTrait
{
    use SkuGroupResponsePrimaryFieldsTrait;
    use SkuResponsePrimaryFieldsTrait;

    /**
     * Gets the standard SKU response fields including nested SKU Group.
     *
     * @return string
     */
    protected function getSkuResponseFields() : string
    {
        return $this->getSkuResponsePrimaryFields().' skuGroup {'.$this->getSkuGroupResponsePrimaryFields().'}';
    }
}
