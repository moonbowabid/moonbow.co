<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\HasProductAttributeMappingServiceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter to convert Product variation attribute mapping to GraphQL SKU attributeValues format.
 */
class AttributeValuesAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;
    use HasProductAttributeMappingServiceTrait;

    /** @var Product */
    protected Product $source;

    /**
     * Constructor.
     *
     * @param Product $source
     */
    public function __construct(Product $source)
    {
        $this->source = $source;
    }

    /**
     * Convert Product variation attributes to GraphQL SKU attributeValues format.
     *
     * @return array<string, mixed>[]
     */
    public function convertFromSource() : array
    {
        // Only process variation products
        if ($this->source->getType() !== 'variation') {
            return [];
        }

        // Get variant attribute mapping from Product model (already parsed AttributeValue objects)
        $variantAttributeMapping = $this->source->getVariantAttributeMapping();

        if (empty($variantAttributeMapping)) {
            return [];
        }

        $attributeValues = [];

        foreach ($variantAttributeMapping as $attributeName => $attributeValue) {
            // Skip "Any" attributes or empty values
            if (! $attributeValue || '' === $attributeValue->getName()) {
                continue;
            }

            // Convert to GraphQL AssociateAttributeValueToSKUInput format
            $attributeValues[] = [
                'attributeName' => $this->getProductAttributeMappingService()->getRemoteAttributeNameForLocalAttributeSlug($attributeName),
                'valueName'     => $this->getProductAttributeMappingService()->getRemoteAttributeValueNameForLocalAttributeValueSlug($attributeName, $attributeValue->getName()),
            ];
        }

        return $attributeValues;
    }

    /**
     * Convert from V2 AttributeValue DTOs back to Product variant attribute mapping.
     * This is the reverse operation - not typically used.
     *
     * @return array<string, \GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\AttributeValue>
     */
    public function convertToSource() : array
    {
        // This adapter primarily converts FROM Product TO AttributeValue DTOs
        // Reverse conversion would require different logic and is not typically needed
        return [];
    }
}
