<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductAttributeMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\AttributeValueUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductAttributesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AttributeValue;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

class AttributeValueUpdatesBuilder
{
    use CanConvertProductAttributesTrait;

    /**
     * Build AttributeValueUpdates object for the given operation and SKU group.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return AttributeValueUpdates|null
     */
    public function build(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : ?AttributeValueUpdates
    {
        $this->setProductAttributeMappingService(ProductAttributeMappingService::for($operation->getProduct()));

        $attributeValueUpdates = AttributeValueUpdates::getNewInstance([]);

        if ($toRemove = $this->getAttributeValuesToRemove($operation, $skuGroup)) {
            $attributeValueUpdates->toRemove = $toRemove;
        }

        if ($toAdd = $this->getAttributeValuesToAdd($operation, $skuGroup)) {
            $attributeValueUpdates->toAdd = $toAdd;
        }

        return $attributeValueUpdates->hasUpdates() ? $attributeValueUpdates : null;
    }

    /**
     * Get attribute values to remove by comparing Commerce and WooCommerce attribute values.
     * Only processes existing attributes that have matching names.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return array<string, string[]> Map of attribute ID to array of value IDs to remove
     */
    protected function getAttributeValuesToRemove(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : array
    {
        $valuesToRemove = [];

        // Get current attributes from Commerce SkuGroup
        $currentCommerceAttributes = $skuGroup->attributes;
        if (empty($currentCommerceAttributes)) {
            return [];
        }

        // Convert WooCommerce attributes to Commerce format for comparison
        $localAttributes = $this->convertProductAttributes($operation->getProduct());

        // Create a lookup map of WooCommerce attribute names to their values
        $localAttributeValuesByName = $this->groupAttributeValuesByAttributeName($localAttributes);

        // Process each existing Commerce attribute
        foreach ($currentCommerceAttributes as $commerceAttribute) {
            if (empty($commerceAttribute->id) || empty($commerceAttribute->name)) {
                continue; // Skip attributes without ID or name
            }

            $attributeId = $commerceAttribute->id;
            $attributeName = $commerceAttribute->name;

            // Check if this attribute still exists in WooCommerce
            if (! isset($localAttributeValuesByName[$attributeName])) {
                // Attribute doesn't exist in WooCommerce anymore - this will be handled
                // by AttributeUpdates (removing the entire attribute), so skip here
                continue;
            }

            $valueIdsToRemove = $this->findCommerceValuesIdsToRemove($localAttributeValuesByName[$attributeName], $commerceAttribute);

            // Only add to removal list if there are values to remove
            if (! empty($valueIdsToRemove)) {
                $valuesToRemove[$attributeId] = $valueIdsToRemove;
            }
        }

        return $valuesToRemove;
    }

    /**
     * Get attribute values to add by comparing WooCommerce and Commerce attribute values.
     * Only processes existing attributes that have matching names.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return array<string, AttributeValue[]> Map of attribute ID to array of values to add
     */
    protected function getAttributeValuesToAdd(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : array
    {
        $valuesToAdd = [];

        // Get current attributes from Commerce SkuGroup
        $currentCommerceAttributes = $skuGroup->attributes;
        if (empty($currentCommerceAttributes)) {
            return []; // No existing attributes to add values to
        }

        // Convert WooCommerce attributes to Commerce format for comparison
        $localAttributes = $this->convertProductAttributes($operation->getProduct());

        // Create a lookup map of WooCommerce attribute names to their values
        $localAttributeValuesByName = $this->groupAttributeValuesByAttributeName($localAttributes);

        // Process each existing Commerce attribute
        foreach ($currentCommerceAttributes as $commerceAttribute) {
            if (empty($commerceAttribute->id) || empty($commerceAttribute->name)) {
                continue; // Skip attributes without ID or name
            }

            $attributeId = $commerceAttribute->id;
            $attributeName = $commerceAttribute->name;

            // Check if this attribute exists in WooCommerce
            if (! isset($localAttributeValuesByName[$attributeName])) {
                // Attribute doesn't exist in WooCommerce - no values to add
                continue;
            }
            $newValues = $this->findMissingCommerceValues($commerceAttribute, $localAttributeValuesByName[$attributeName]);

            // Only add to addition list if there are values to add
            if (! empty($newValues)) {
                $valuesToAdd[$attributeId] = $newValues;
            }
        }

        return $valuesToAdd;
    }

    /**
     * Groups attribute values by their parent attribute name for easy lookup.
     *
     * @param Attribute[] $attributes
     * @return array<string, AttributeValue[]> Map of attribute name to array of values
     */
    protected function groupAttributeValuesByAttributeName(array $attributes) : array
    {
        $grouped = [];

        foreach ($attributes as $attribute) {
            if (empty($attribute->name)) {
                continue;
            }

            $grouped[$attribute->name] = $attribute->values ?? [];
        }

        return $grouped;
    }

    /**
     * Find Commerce attribute values that don't exist in WooCommerce.
     *
     * @param AttributeValue[] $localAttributeValuesByName
     * @param Attribute $commerceAttribute
     * @return string[] Array of value IDs to remove
     */
    public function findCommerceValuesIdsToRemove(array $localAttributeValuesByName, Attribute $commerceAttribute) : array
    {
        // Get WooCommerce value names for this attribute
        $localValueNames = array_map(fn (AttributeValue $value) => $value->name, $localAttributeValuesByName);

        // Find Commerce values that don't exist in WooCommerce
        $valueIdsToRemove = [];
        foreach ($commerceAttribute->values as $commerceValue) {
            if (empty($commerceValue->id) || empty($commerceValue->name)) {
                continue; // Skip values without ID or name
            }

            if (! in_array($commerceValue->name, $localValueNames, true) && TypeHelper::stringOrNull($commerceValue->id) !== null) {
                $valueIdsToRemove[] = $commerceValue->id;
            }
        }

        return $valueIdsToRemove;
    }

    /**
     * @param Attribute $commerceAttribute
     * @param AttributeValue[] $localAttributeValuesByName
     * @return AttributeValue[]
     */
    public function findMissingCommerceValues(Attribute $commerceAttribute, array $localAttributeValuesByName) : array
    {
        // Get Commerce value names for this attribute
        $commerceValueNames = array_map(fn (AttributeValue $value) => $value->name, $commerceAttribute->values);

        // Find WooCommerce values that don't exist in Commerce
        $newValues = [];
        foreach ($localAttributeValuesByName as $localValue) {
            if (empty($localValue->name)) {
                continue; // Skip values without name
            }

            if (! in_array($localValue->name, $commerceValueNames, true)) {
                $newValues[] = $localValue;
            }
        }

        return $newValues;
    }
}
