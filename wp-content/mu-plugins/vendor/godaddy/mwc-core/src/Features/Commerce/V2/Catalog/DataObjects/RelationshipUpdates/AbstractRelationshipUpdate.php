<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\Contracts\RelationshipUpdatesInterface;

abstract class AbstractRelationshipUpdate extends AbstractDataObject implements RelationshipUpdatesInterface
{
    public function hasUpdates() : bool
    {
        $updates = array_filter($this->toArray());

        return ! empty($updates);
    }
}
