<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Trait for cleaning attribute data by removing id fields for creation operations.
 */
trait CanCleanAttributeDataTrait
{
    /**
     * Clean attribute data for creation by removing id fields from attributes and values.
     *
     * @param array<mixed, mixed> $attributes
     * @return array<array<string, mixed>>
     */
    protected function cleanAttributesForCreation(array $attributes) : array
    {
        $cleanedAttributes = [];

        foreach ($attributes as $attribute) {
            if (is_array($attribute)) {
                $cleanedAttributes[] = $this->cleanSingleAttributeForCreation(TypeHelper::arrayOfStringsAsKeys($attribute));
            }
        }

        return $cleanedAttributes;
    }

    /**
     * Clean a single attribute array by removing id fields from the attribute and its values.
     *
     * @param array<string, mixed> $attribute
     * @return array<string, mixed>
     */
    protected function cleanSingleAttributeForCreation(array $attribute) : array
    {
        // Remove id field from attribute
        $attribute = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::except($attribute, 'id'));

        // Convert nested AttributeValue objects to GraphQL AssociateAttributeValueInput format
        if (ArrayHelper::accessible($attribute['values'] ?? null)) {
            $values = [];
            foreach ($attribute['values'] as $value) {
                if (ArrayHelper::accessible($value)) {
                    // Remove ID from values as well
                    $values[] = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::except($value, 'id'));
                }
            }
            $attribute['values'] = $values;
        }

        return $attribute;
    }
}
