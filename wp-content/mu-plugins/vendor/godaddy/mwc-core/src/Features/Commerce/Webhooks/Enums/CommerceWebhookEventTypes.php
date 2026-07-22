<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Enums;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;

/**
 * Enum-like class for defining Commerce webhook event types.
 */
class CommerceWebhookEventTypes
{
    use EnumTrait;

    public const CategoryCreated = 'commerce.category.created';
    public const CategoryDeleted = 'commerce.category.deleted';
    public const CategoryUpdated = 'commerce.category.updated';
    public const ProductCreated = 'commerce.product.created';
    public const ProductDeleted = 'commerce.product.deleted';
    public const ProductUpdated = 'commerce.product.updated';

    // v2 events
    public const SkuCreated = 'commerce.catalog.sku.created';
    public const SkuGroupUpdated = 'commerce.catalog.sku-group.updated';
    public const SkuUpdated = 'commerce.catalog.sku.updated';
    public const SkuPriceUpdated = 'commerce.catalog.sku-price.updated';
    public const SkuGroupMediaObjectsAdded = 'commerce.catalog.sku-group.media-objects.added';
    public const SkuMediaObjectsAdded = 'commerce.catalog.sku.media-objects.added';
    public const SkuGroupMediaObjectsUpdated = 'commerce.catalog.sku-group.media-objects.updated';
    public const SkuMediaObjectsUpdated = 'commerce.catalog.sku.media-objects.updated';
    public const SkuGroupMediaObjectsRemoved = 'commerce.catalog.sku-group.media-objects.removed';
    public const SkuMediaObjectsRemoved = 'commerce.catalog.sku.media-objects.removed';
    public const SkuGroupAttributesRemoved = 'commerce.catalog.sku-group.attributes.removed';
    public const AttributeCreated = 'commerce.catalog.attribute.created'; // despite the naming, this fires when an attribute is added to a sku group and does contain the skuGroupId in the payload
    public const SkuGroupListsAdded = 'commerce.catalog.sku-group.lists.added';
    public const SkuGroupListsRemoved = 'commerce.catalog.sku-group.lists.removed';
    public const AttributeUpdated = 'commerce.catalog.attribute.updated';
    public const SkuAttributeValuesAdded = 'commerce.catalog.sku.attribute-values.added';
    public const SkuAttributeValuesRemoved = 'commerce.catalog.sku.attribute-values.removed';
    public const ListCreated = 'commerce.catalog.list.created';
    public const ListUpdated = 'commerce.catalog.list.updated';
    public const ListTreeNodeCreated = 'commerce.catalog.list-tree-node.created';
}
