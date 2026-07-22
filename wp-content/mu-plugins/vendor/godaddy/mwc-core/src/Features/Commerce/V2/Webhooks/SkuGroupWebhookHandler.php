<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

/**
 * Handles webhooks related to top-level sku group objects, including:
 * - commerce.catalog.sku-group.updated
 */
class SkuGroupWebhookHandler extends AbstractSkuGroupWebhookHandler
{
    /** {@inheritDoc} */
    protected function getPathToSkuGroupIdProperty() : string
    {
        return 'data.id';
    }
}
