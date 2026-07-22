<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\ProductToMediaObjectAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Trait for converting Product media objects to MediaObject references.
 */
trait CanConvertProductMediaObjectsTrait
{
    /**
     * Convert Product images to MediaObject references.
     *
     * @param Product $product
     * @return MediaObject[]
     */
    protected function convertProductMediaObjects(Product $product) : array
    {
        $adapter = ProductToMediaObjectAdapter::getNewInstance();

        return $adapter->convertToSource($product);
    }
}
