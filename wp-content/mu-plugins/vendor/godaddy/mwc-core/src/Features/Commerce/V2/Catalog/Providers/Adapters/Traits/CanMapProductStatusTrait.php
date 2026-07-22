<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

trait CanMapProductStatusTrait
{
    /**
     * Map a core {@see Product} model status to Catalog v2 product status. This is the same for both {@see Sku} and {@see SkuGroup}.
     *
     * @param string|null $productStatus
     * @return string
     */
    protected function mapProductStatus(?string $productStatus) : string
    {
        switch ($productStatus) {
            case 'publish':
                return 'ACTIVE';
            case 'deleted':
            case 'trash':
                return 'ARCHIVED';
            default:
                // 'draft' and `private` will end up here
                return 'DRAFT';
        }
    }
}
