<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

/**
 * Trait for removing null properties from arrays and nested arrays.
 */
trait CanRemoveNullPropertiesTrait
{
    /**
     * Recursively removes null properties from arrays and nested arrays.
     * Useful for cleaning creation payloads where certain properties don't exist yet.
     *
     * @param array<mixed, mixed> $data
     * @param string $property Property name to remove when null (defaults to 'id')
     * @return array<mixed, mixed>
     */
    protected function removeNullProperty(array $data, string $property = 'id') : array
    {
        $result = [];

        foreach ($data as $key => $value) {
            // Skip null properties matching the target property name
            if ($key === $property && $value === null) {
                continue;
            }

            // Recursively process arrays
            if (is_array($value)) {
                $value = $this->removeNullProperty($value, $property);
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
