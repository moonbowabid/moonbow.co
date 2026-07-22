<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Generic relationship updates for commerce objects (SKUs, SKU Groups, etc.).
 */
class RelationshipUpdates extends AbstractDataObject
{
    public ?MediaUpdates $mediaUpdates = null;

    public ?PriceUpdates $priceUpdates = null;

    public ?ChannelUpdates $channelUpdates = null;

    public ?AttributeUpdates $attributeUpdates = null;

    public ?AttributeValueUpdates $attributeValueUpdates = null;

    public ?ListUpdates $listUpdates = null;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function hasUpdates() : bool
    {
        $updates = array_filter($this->toArray());

        return ! empty($updates);
    }
}
