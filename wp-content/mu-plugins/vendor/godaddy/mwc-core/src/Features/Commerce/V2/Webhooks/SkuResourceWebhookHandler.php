<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

/**
 * Handles webhooks related to sku relationship resources, including:
 * - commerce.catalog.sku-price.updated
 */
class SkuResourceWebhookHandler extends AbstractSkuWebhookHandler
{
    /** {@inheritDoc} */
    protected function getPathToSkuIdProperty() : string
    {
        return 'data.skuId';
    }
}
