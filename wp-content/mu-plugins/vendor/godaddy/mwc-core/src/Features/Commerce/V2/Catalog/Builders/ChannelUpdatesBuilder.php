<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\ChannelUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Builder for channel relationship updates.
 * This handles associating / disassociating a channel from a given Sku Group.
 */
class ChannelUpdatesBuilder
{
    /**
     * Build channel updates for a SkuGroup based on product operation.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return ChannelUpdates|null
     */
    public function build(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : ?ChannelUpdates
    {
        $channelIdsToRemove = $operation->getChannelIds()->remove;
        if (empty($channelIdsToRemove)) {
            return null;
        }

        /*
         * We only want to remove channel IDs that already exist on the SkuGroup.
         * So we match the provided `$channelIdsToRemove` list against the actual list `$skuGroup->channels`
         * and build a new list of IDs that are effectively in both places.
         */
        $existingChannelIds = array_map(fn ($channel) => $channel->channelId, $skuGroup->channels);
        $validChannelIdsToRemove = array_filter(
            $channelIdsToRemove,
            fn ($channelId) => ! empty($channelId) && in_array($channelId, $existingChannelIds, true)
        );

        if (empty($validChannelIdsToRemove)) {
            return null;
        }

        $channelUpdates = new ChannelUpdates([
            'toRemove' => array_values($validChannelIdsToRemove),
        ]);

        return $channelUpdates->hasUpdates() ? $channelUpdates : null;
    }
}
