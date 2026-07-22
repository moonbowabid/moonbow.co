<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantListOption;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VariantOptionMapping;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Value;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Facilitates conversion of Commerce v2 attributes (and their values) into corresponding v1 options (and their values + mappings).
 */
trait CanConvertAttributesToOptionsTrait
{
    /**
     * Converts v2 attributes (at the sku level) into v1 option objects.
     *
     * @return AbstractOption[]|null
     */
    protected function convertOptionsFromSku(Sku $sku) : ?array
    {
        return $this->convertOptionsFromAttributes($sku->attributes) ?: null;
    }

    /**
     * Converts an array of v2 {@see Attribute} objects into an array of v1 {@see AbstractOption} objects.
     *
     * @param Attribute[] $attributes
     * @return AbstractOption[]
     */
    protected function convertOptionsFromAttributes(array $attributes) : array
    {
        $options = [];

        foreach ($attributes as $attribute) {
            $options[] = new VariantListOption([
                'name'         => $attribute->name,
                'presentation' => $attribute->label,
                'values'       => $this->convertValuesFromAttribute($attribute),
            ]);
        }

        return $options;
    }

    /**
     * Converts v2 {@see AttributeValue} objects into v1 {@see Value} objects.
     *
     * @return Value[]
     */
    protected function convertValuesFromAttribute(Attribute $attribute) : array
    {
        $values = [];

        foreach ($attribute->values as $value) {
            $values[] = new Value([
                'name'         => $value->name,
                'presentation' => $value->label,
            ]);
        }

        return $values;
    }

    /**
     * Converts v2 options (as the sku group level) into v1 {@see AbstractOption} objects.
     *
     * @return AbstractOption[]|null
     */
    protected function convertOptionsFromSkuGroup(SkuGroup $skuGroup) : ?array
    {
        return $this->convertOptionsFromAttributes($skuGroup->attributes) ?: null;
    }

    /**
     * Converts v2 attribute values associated with a sku into v1 {@see VariantOptionMapping} objects.
     *
     * @return VariantOptionMapping[]|null
     */
    protected function convertVariantOptionMappingFromSku(Sku $sku) : ?array
    {
        $thisSkuAttributeValues = [];

        // create a mapping of all attribute value IDs associated with the sku for later reference
        foreach ($sku->attributeValues as $attributeValue) {
            $thisSkuAttributeValues[$attributeValue->id] = true;
        }

        $mappings = [];

        // $sku->attributes will be _all_ attributes available on the sku group, which is why we have to check if the given
        // value exists in $thisSkuAttributeValues above
        foreach ($sku->attributes as $attribute) {
            foreach ($attribute->values as $attributeValue) {
                if (isset($thisSkuAttributeValues[$attributeValue->id])) {
                    $mappings[] = new VariantOptionMapping([
                        'name'  => $attribute->name,
                        'value' => $attributeValue->label,
                    ]);
                }
            }
        }

        return $mappings ?: null;
    }
}
