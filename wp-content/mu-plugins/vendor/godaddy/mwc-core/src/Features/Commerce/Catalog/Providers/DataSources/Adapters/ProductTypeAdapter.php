<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

class ProductTypeAdapter
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a ProductBase object to a WooCommerce product type string.
     *
     * @param ProductBase $productBase
     * @return string
     */
    public function convertFromSource(ProductBase $productBase) : string
    {
        if (! empty($productBase->parentId)) {
            return 'variation';
        } elseif (! empty($productBase->variants)) {
            // if a product has variants, it's a variable product.
            return 'variable';
        }

        return 'simple';
    }
}
