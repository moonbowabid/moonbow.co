<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

/**
 * Trait for mapping catalog API types to ProductBase types.
 */
trait CanMapProductTypesTrait
{
    /**
     * Map catalog API type to ProductBase type.
     *
     * @param string $catalogType
     * @return string
     */
    protected function mapCatalogTypeToProductType(string $catalogType) : string
    {
        switch (strtoupper($catalogType)) {
            case 'DIGITAL':
                return ProductBase::TYPE_DIGITAL;
            case 'SERVICE':
                return ProductBase::TYPE_SERVICE;
            default:
                return ProductBase::TYPE_PHYSICAL;
        }
    }

    /**
     * Map catalog API status to ProductBase active flag.
     *
     * @param string $status
     * @return bool
     */
    protected function mapCatalogStatusToActive(string $status) : bool
    {
        return strtoupper($status) === 'ACTIVE';
    }
}
