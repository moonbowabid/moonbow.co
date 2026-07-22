<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;

trait CanDetermineShouldPrimeProductsCacheTrait
{
    use CanDetermineShouldReadProductsTrait;

    /**
     * Determines whether the Inventory integration has the 'read' capability enabled.
     */
    protected static function hasInventoryReadCapability() : bool
    {
        return InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Determines whether components should try to prime cache for product information
     * before WooCommerce tries to instantiate those products.
     */
    protected static function shouldPrimeProductsCache() : bool
    {
        return static::hasCatalogReadCapability() || static::hasInventoryReadCapability();
    }
}
