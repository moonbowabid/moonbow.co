<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;

trait CanDetermineStaleProductWebhookTrait
{
    /**
     * Determines if a product webhook is stale (i.e. the local product has been updated since the webhook was created).
     * This is to account for edge cases where there might be a delay in processing the webhook after it is created,
     * and in the meantime the local product has been edited with new data that we don't want to overwrite.
     *
     * @param Webhook $webhook
     * @param int $localProductId
     * @return bool
     */
    protected function isStaleProductWebhook(Webhook $webhook, int $localProductId) : bool
    {
        $localProductLastUpdatedAt = $this->getLocalProductLastUpdatedTimestamp($localProductId);
        if (! $localProductLastUpdatedAt) {
            return false;
        }

        $webhookDateTimeString = TypeHelper::string(ArrayHelper::get(json_decode($webhook->payload, true), 'timestamp'), '');
        $webhookTimestamp = $webhookDateTimeString ? strtotime($webhookDateTimeString) : null;

        if ($webhookTimestamp && $webhookTimestamp <= $localProductLastUpdatedAt) {
            return true;
        }

        /*
         * We very intentionally do not check the `updatedAt` value of the remote object because adding/removing
         * sku/group relationships does not "touch" the parent resource's `updatedAt` value. For example: using the
         * mutation to add a new media to a sku does not update the sku's updatedAt value.
         */

        return false;
    }

    /**
     * Gets the last updated timestamp of a local product in GMT.
     *
     * @param int $localProductId
     * @return int|null
     */
    protected function getLocalProductLastUpdatedTimestamp(int $localProductId) : ?int
    {
        $timestamp = CatalogIntegration::withoutReads(fn () => get_post_modified_time('U', true, $localProductId));

        return is_numeric($timestamp) ? (int) $timestamp : null;
    }
}
