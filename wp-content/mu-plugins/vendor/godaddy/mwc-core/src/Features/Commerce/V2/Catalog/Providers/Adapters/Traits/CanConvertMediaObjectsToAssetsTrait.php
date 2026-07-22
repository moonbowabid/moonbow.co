<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\MediaObjectToAssetAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

/**
 * Trait for converting MediaObjects to Assets.
 */
trait CanConvertMediaObjectsToAssetsTrait
{
    /**
     * Converts MediaObjects to Assets.
     *
     * @param MediaObject[] $mediaObjects
     * @return AbstractAsset[]
     */
    protected function convertMediaObjectsToAssets(array $mediaObjects) : array
    {
        $assets = [];
        $adapter = MediaObjectToAssetAdapter::getNewInstance();

        foreach ($mediaObjects as $mediaObject) {
            try {
                $assets[] = $adapter->convertFromSource($mediaObject);
            } catch (AdapterException $e) {
                // Skip invalid media objects and continue processing
                continue;
            }
        }

        return $assets;
    }
}
