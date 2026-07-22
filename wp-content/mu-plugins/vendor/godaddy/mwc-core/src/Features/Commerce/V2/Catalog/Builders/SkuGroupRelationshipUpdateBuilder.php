<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\AttributeUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\AttributeValueUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

class SkuGroupRelationshipUpdateBuilder
{
    /** @var MediaUpdatesBuilder */
    protected MediaUpdatesBuilder $mediaUpdatesBuilder;

    /** @var ChannelUpdatesBuilder */
    protected ChannelUpdatesBuilder $channelUpdatesBuilder;

    protected AttributeUpdatesBuilder $attributeUpdatesBuilder;

    protected AttributeValueUpdatesBuilder $attributeValueUpdatesBuilder;

    /** @var ListUpdatesBuilder */
    protected ListUpdatesBuilder $listUpdatesBuilder;

    public function __construct(
        MediaUpdatesBuilder $mediaUpdatesBuilder,
        ChannelUpdatesBuilder $channelUpdatesBuilder,
        AttributeUpdatesBuilder $attributeUpdatesBuilder,
        AttributeValueUpdatesBuilder $attributeValueUpdatesBuilder,
        ListUpdatesBuilder $listUpdatesBuilder
    ) {
        $this->mediaUpdatesBuilder = $mediaUpdatesBuilder;
        $this->channelUpdatesBuilder = $channelUpdatesBuilder;
        $this->attributeUpdatesBuilder = $attributeUpdatesBuilder;
        $this->attributeValueUpdatesBuilder = $attributeValueUpdatesBuilder;
        $this->listUpdatesBuilder = $listUpdatesBuilder;
    }

    /**
     * Builds relationship updates for the given operation and SKU group.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return RelationshipUpdates
     */
    public function build(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : RelationshipUpdates
    {
        $updates = new RelationshipUpdates();

        if ($mediaUpdates = $this->mediaUpdatesBuilder->build($operation, $skuGroup)) {
            $updates->mediaUpdates = $mediaUpdates;
        }

        if ($channelUpdates = $this->channelUpdatesBuilder->build($operation, $skuGroup)) {
            $updates->channelUpdates = $channelUpdates;
        }

        // Coordinate attribute and attribute value updates
        $this->buildAttributeUpdates($operation, $skuGroup, $updates);

        // @todo Add other relationship types as required
        if ($listUpdates = $this->listUpdatesBuilder->build($operation, $skuGroup)) {
            $updates->listUpdates = $listUpdates;
        }

        return $updates;
    }

    /**
     * Builds coordinated attribute and attribute value updates.
     *
     * Handles the coordination between AttributeUpdates and AttributeValueUpdates
     * to prevent conflicts and duplicate operations on the same attributes.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @param RelationshipUpdates $updates
     */
    protected function buildAttributeUpdates(
        CreateOrUpdateProductOperationContract $operation,
        SkuGroup $skuGroup,
        RelationshipUpdates $updates
    ) : void {
        // Step 1: Build initial attribute updates
        $attributeUpdates = $this->attributeUpdatesBuilder->build($operation, $skuGroup);

        // Step 2: Build initial attribute value updates
        $attributeValueUpdates = $this->attributeValueUpdatesBuilder->build($operation, $skuGroup);

        /*
         * Step 3: Reconcile the updates to ensure we're not adding/removing values where entire attributes
         * are being added or removed.
         * i.e. if an "Attribute A" is being entirely removed, then we should not have updates to also remove every
         * single one of "Attribute A's" values.
         */
        if ($attributeUpdates || $attributeValueUpdates) {
            $this->coordinateAttributeUpdates($attributeUpdates, $attributeValueUpdates, $skuGroup);

            if ($attributeUpdates && $attributeUpdates->hasUpdates()) {
                $updates->attributeUpdates = $attributeUpdates;
            }

            if ($attributeValueUpdates && $attributeValueUpdates->hasUpdates()) {
                $updates->attributeValueUpdates = $attributeValueUpdates;
            }
        }
    }

    /**
     * Coordinates AttributeUpdates and AttributeValueUpdates to avoid conflicts and duplicate operations.
     *
     * Ensures we're not adding/removing individual attribute values where entire attributes
     * are being added or removed. For example, if "Attribute A" is being entirely removed,
     * we should not also have individual operations to remove each of "Attribute A's" values.
     *
     * Strategy:
     * 1. AttributeValueUpdates handles individual value additions and removals for stable attributes
     * 2. AttributeUpdates handles entire attribute recreation (with nested values)
     * 3. Coordination prevents duplicate operations on the same attributes
     *
     * @param ?AttributeUpdates $attributeUpdates
     * @param ?AttributeValueUpdates $attributeValueUpdates
     * @param SkuGroup $skuGroup
     */
    protected function coordinateAttributeUpdates(
        ?AttributeUpdates $attributeUpdates,
        ?AttributeValueUpdates $attributeValueUpdates,
        SkuGroup $skuGroup
    ) : void {
        if (! $attributeUpdates || ! $attributeValueUpdates) {
            return; // No coordination needed if one is null
        }

        // Get attribute IDs that will be removed or re-added by AttributeUpdates
        $attributeIdsBeingModified = $this->getAttributeIdsBeingModifiedByAttributeUpdates($attributeUpdates, $skuGroup);

        // Remove any attribute value updates for attributes that are being modified by AttributeUpdates
        $this->filterAttributeValueUpdates($attributeValueUpdates, $attributeIdsBeingModified);
    }

    /**
     * Gets attribute IDs that will be modified (removed or re-added) by AttributeUpdates.
     *
     * @param ?AttributeUpdates $attributeUpdates
     * @param SkuGroup $skuGroup
     * @return string[] Array of attribute IDs being modified
     */
    protected function getAttributeIdsBeingModifiedByAttributeUpdates(?AttributeUpdates $attributeUpdates, SkuGroup $skuGroup) : array
    {
        // Attributes being removed
        $modifiedIds = $attributeUpdates->toRemove ?? [];

        // Attributes being added - get IDs of any existing attributes with same names
        if (! empty($attributeUpdates->toAdd)) {
            $existingAttributesByName = $this->groupAttributesByName($skuGroup->attributes);

            foreach ($attributeUpdates->toAdd as $attributeToAdd) {
                if (isset($existingAttributesByName[$attributeToAdd->name]) &&
                    $existingAttributesByName[$attributeToAdd->name]->id) {
                    $modifiedIds[] = $existingAttributesByName[$attributeToAdd->name]->id;
                }
            }
        }

        return array_unique($modifiedIds);
    }

    /**
     * Groups attributes by their name for easy lookup.
     *
     * @param Attribute[] $attributes
     * @return array<string, Attribute> Map of attribute name to Attribute object
     */
    protected function groupAttributesByName(array $attributes) : array
    {
        $grouped = [];

        foreach ($attributes as $attribute) {
            if (! empty($attribute->name)) {
                $grouped[$attribute->name] = $attribute;
            }
        }

        return $grouped;
    }

    /**
     * Removes attribute value updates for attributes that are being modified by AttributeUpdates.
     *
     * @param AttributeValueUpdates $attributeValueUpdates
     * @param string[] $attributeIdsBeingModified
     */
    protected function filterAttributeValueUpdates(AttributeValueUpdates $attributeValueUpdates, array $attributeIdsBeingModified) : void
    {
        if (empty($attributeIdsBeingModified)) {
            return;
        }

        // Remove any value updates (both additions and removals) for attributes that will be modified by AttributeUpdates
        foreach ($attributeIdsBeingModified as $attributeId) {
            unset($attributeValueUpdates->toRemove[$attributeId], $attributeValueUpdates->toAdd[$attributeId]);
        }
    }
}
