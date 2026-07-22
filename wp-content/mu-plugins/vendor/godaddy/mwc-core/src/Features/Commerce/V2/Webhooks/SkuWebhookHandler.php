<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

/**
 * Handles webhooks related to top-level sku objects, including:
 * - commerce.catalog.sku.created
 * - commerce.catalog.sku.updated
 */
class SkuWebhookHandler extends AbstractSkuWebhookHandler
{
    /** {@inheritDoc} */
    protected function getPathToSkuIdProperty() : string
    {
        return 'data.id';
    }
}
