<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;

trait CanConvertListsToCategoryIdsTrait
{
    /**
     * Converts an array of {@see ListObject} DTOs into just an array of their UUIDs.
     *
     * @param ListObject[] $lists
     * @return string[]
     */
    protected function convertListObjectsToCategoryIds(array $lists) : array
    {
        return array_values(array_filter(array_map(fn (ListObject $list) => $list->id, $lists)));
    }
}
