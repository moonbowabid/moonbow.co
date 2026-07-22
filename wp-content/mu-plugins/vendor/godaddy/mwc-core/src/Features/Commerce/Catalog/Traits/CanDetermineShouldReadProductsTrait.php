<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;

trait CanDetermineShouldReadProductsTrait
{
    /**
     * Determines whether the Catalog integration has the 'read' capability enabled.
     */
    protected static function hasCatalogReadCapability() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Determines whether we should read related products.
     */
    protected function shouldReadProducts() : bool
    {
        return static::hasCatalogReadCapability();
    }
}
