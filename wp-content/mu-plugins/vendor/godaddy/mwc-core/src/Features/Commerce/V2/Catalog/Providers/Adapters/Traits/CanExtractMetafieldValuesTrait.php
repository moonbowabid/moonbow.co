<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Metafield;

/**
 * Trait for extracting values from metafields arrays.
 *
 * Works with objects that have a `metafields` property containing Metafield[] objects.
 */
trait CanExtractMetafieldValuesTrait
{
    /**
     * Gets metafield value by key from an object's metafields array.
     *
     * Searches metafields with namespace priority:
     * - First checks 'commerce-apps' namespace
     * - Then checks 'catalog-v1-product' namespace (for backwards compatibility)
     *
     * @param object{metafields?: Metafield[]} $object object with metafields property
     * @param string $key metafield key to search for
     * @return string|null metafield value, or null if not found or empty
     */
    protected function getMetafieldValue(object $object, string $key) : ?string
    {
        if (! isset($object->metafields) || empty($object->metafields)) {
            return null;
        }

        // First priority: commerce-apps namespace
        foreach ($object->metafields as $metafield) {
            if ($metafield->key === $key && $metafield->namespace === 'commerce-apps') {
                return $metafield->value !== '' ? $metafield->value : null;
            }
        }

        // Fallback: catalog-v1-product namespace
        foreach ($object->metafields as $metafield) {
            if ($metafield->key === $key && $metafield->namespace === 'catalog-v1-product') {
                return $metafield->value !== '' ? $metafield->value : null;
            }
        }

        return null;
    }

    /**
     * Extracts low inventory threshold from metafields.
     *
     * @param object{metafields?: Metafield[]}|null $object object with metafields property
     * @return int|null
     */
    protected function extractLowInventoryThreshold(?object $object) : ?int
    {
        if (! $object) {
            return null;
        }

        $value = $this->getMetafieldValue($object, 'lowInventoryThreshold');

        return $value !== null ? TypeHelper::int($value, 0) : null;
    }
}
