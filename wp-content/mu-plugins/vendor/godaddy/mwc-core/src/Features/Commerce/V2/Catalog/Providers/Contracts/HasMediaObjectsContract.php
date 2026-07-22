<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

/**
 * Contract for objects that have media objects.
 */
interface HasMediaObjectsContract
{
    /**
     * Gets the media objects.
     *
     * @return MediaObject[]
     */
    public function getMediaObjects() : array;
}
