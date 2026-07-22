<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

/**
 * Adapter to convert MediaObjects specifically to ImageAssets.
 */
class MediaObjectToImageAssetAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts MediaObjects to ImageAssets.
     *
     * @param MediaObject[] $mediaObjects
     * @return ImageAsset[]
     */
    public function convertMediaObjectsToImageAssets(array $mediaObjects) : array
    {
        $imageAssets = [];

        foreach ($mediaObjects as $mediaObject) {
            // Only process IMAGE type MediaObjects
            if ($mediaObject->type === 'IMAGE') {
                try {
                    $imageAssets[] = $this->convertFromSource($mediaObject);
                } catch (AdapterException $e) {
                    // Skip invalid media objects and continue processing
                    continue;
                }
            }
        }

        return $imageAssets;
    }

    /**
     * Converts a MediaObject to an ImageAsset.
     *
     * @param MediaObject $source
     * @return ImageAsset
     * @throws AdapterException
     */
    public function convertFromSource($source = null) : ImageAsset
    {
        if (! $source instanceof MediaObject) {
            throw new AdapterException('Invalid media object. Expected instance of MediaObject.');
        }

        if ($source->type !== 'IMAGE') {
            throw new AdapterException('MediaObject must be of type IMAGE to convert to ImageAsset.');
        }

        return new ImageAsset([
            'contentType' => null,
            'name'        => $source->name ?: '',
            'thumbnail'   => $source->url,
            'url'         => $source->url,
        ]);
    }

    /**
     * Converts to source format (not implemented for this adapter).
     *
     * @return array<string, mixed>
     */
    public function convertToSource() : array
    {
        return [];
    }
}
