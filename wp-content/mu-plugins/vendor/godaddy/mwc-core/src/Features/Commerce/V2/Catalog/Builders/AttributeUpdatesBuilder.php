<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute as CommonAttribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductAttributeMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\AttributeUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductAttributesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class AttributeUpdatesBuilder
{
    use CanConvertProductAttributesTrait;

    /**
     * Build AttributeUpdates object for the given operation and SKU group.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return AttributeUpdates|null
     */
    public function build(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : ?AttributeUpdates
    {
        $this->setProductAttributeMappingService(ProductAttributeMappingService::for($operation->getProduct()));

        $attributeUpdates = AttributeUpdates::getNewInstance([]);

        if ($toRemove = $this->getAttributesToRemove($operation, $skuGroup)) {
            $attributeUpdates->toRemove = $toRemove;
        }

        if ($toAdd = $this->getAttributesToAdd($operation, $skuGroup)) {
            $attributeUpdates->toAdd = $toAdd;
        }

        return $attributeUpdates->hasUpdates() ? $attributeUpdates : null;
    }

    /**
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return string[] array of Commerce attribute IDs
     */
    protected function getAttributesToRemove(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : array
    {
        // Current attributes on the Commerce object
        $currentAttributeObjects = $skuGroup->attributes;
        if (empty($currentAttributeObjects)) {
            return [];
        }

        // Get attribute slugs from the Woo object for comparison
        $remoteAttributeNamesForLocalAttributes = $this->getRemoteAttributeNamesForLocalAttributeSlugs($operation);

        // If no slugs, remove all current attributes with valid IDs.
        if (empty($remoteAttributeNamesForLocalAttributes)) {
            $attributeIds = array_map(fn (Attribute $attribute) => $attribute->id, $currentAttributeObjects);

            return array_values(array_filter($attributeIds));
        }

        // Find all attributes on the Commerce object that aren't in the $attributeSlugs array.
        $attributeIdsToRemove = [];
        foreach ($currentAttributeObjects as $attributeObject) {
            if (! in_array($attributeObject->name, $remoteAttributeNamesForLocalAttributes, true) && $attributeObject->id !== null) {
                $attributeIdsToRemove[] = $attributeObject->id;
            }
        }

        return $attributeIdsToRemove;
    }

    /**
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return Attribute[]
     */
    protected function getAttributesToAdd(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : array
    {
        // Convert the local product attributes into Commerce attributes
        $localProductAttributes = $this->convertProductAttributes($operation->getProduct());
        if (empty($localProductAttributes)) {
            return [];
        }

        // Get current attribute slugs for comparison
        $currentCommerceSlugs = $this->getCommerceAttributeSlugs($skuGroup);

        // Check if any attributes need to be added.
        $attributesToAdd = [];
        foreach ($localProductAttributes as $localProductAttribute) {
            if (! in_array($localProductAttribute->name, $currentCommerceSlugs, true)) {
                $attributesToAdd[] = $localProductAttribute;
            }
        }

        return $attributesToAdd;
    }

    /**
     * @return string[]
     */
    protected function getCommerceAttributeSlugs(SkuGroup $skuGroup) : array
    {
        return array_values(
            array_filter(
                array_map(fn (Attribute $attribute) => $attribute->name, $skuGroup->attributes)
            )
        );
    }

    /**
     * @param CreateOrUpdateProductOperationContract $operation
     * @return string[] array of slugs
     */
    protected function getRemoteAttributeNamesForLocalAttributeSlugs(CreateOrUpdateProductOperationContract $operation) : array
    {
        $product = $operation->getProduct();

        $productAttributes = $product->getAttributes();
        if (empty($productAttributes)) {
            return [];
        }

        $productAttributeMappingService = $this->getProductAttributeMappingService();

        return array_values(array_filter(array_map(function (CommonAttribute $commonAttribute) use ($productAttributeMappingService) {
            return $productAttributeMappingService->getRemoteAttributeNameForLocalAttributeSlug($commonAttribute->getName());
        }, $productAttributes)));
    }
}
