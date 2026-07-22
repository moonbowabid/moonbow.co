<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Trait for converting Product timestamps for Commerce API consumption.
 */
trait CanConvertProductTimestampsTrait
{
    /**
     * Format DateTime object for API consumption.
     *
     * @param \DateTime|null $dateTime
     * @return string|null
     */
    protected function formatDateTimeForApi(?\DateTime $dateTime) : ?string
    {
        return $dateTime ? $dateTime->format('Y-m-d\TH:i:s.v\Z') : null;
    }

    /**
     * Get archived date from Product.
     *
     * @param Product $product
     * @return string|null
     */
    protected function getArchivedAt(Product $product) : ?string
    {
        // For now, only set archivedAt if product status indicates it's archived/trashed
        if (in_array($product->getStatus(), ['trash', 'archived'], true)) {
            // Use updated date as archive date since WordPress doesn't track archive dates separately
            return $this->formatDateTimeForApi($product->getUpdatedAt());
        }

        return null;
    }
}
