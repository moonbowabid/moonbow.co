<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

/**
 * Handles webhooks related to sku group relationship resources, including:
 * - commerce.catalog.sku-group.media-objects.added
 * - commerce.catalog.sku-group.media-objects.updated
 * - commerce.catalog.sku-group.media-objects.removed
 */
class SkuGroupResourceWebhookHandler extends AbstractSkuGroupWebhookHandler
{
    /** {@inheritDoc} */
    protected function getPathToSkuGroupIdProperty() : string
    {
        return 'data.skuGroupId';
    }
}
