<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VideoAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

/**
 * Adapter to convert Commerce API Media objects to WordPress Asset objects.
 */
class MediaObjectToAssetAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a Commerce API Media object to an Asset object.
     *
     * @param MediaObject $source
     * @return AbstractAsset
     * @throws AdapterException
     */
    public function convertFromSource($source = null) : AbstractAsset
    {
        if (! $source instanceof MediaObject) {
            throw new AdapterException('Invalid media object. Expected instance of MediaObject.');
        }

        $assetData = [
            'contentType' => null,
            'name'        => $source->name ?: '',
            'thumbnail'   => $source->url,
            'url'         => $source->url,
        ];

        return $this->createAssetByType($source->type, $assetData);
    }

    /**
     * Creates the appropriate Asset object based on media type.
     *
     * @param string $mediaType
     * @param array<string, mixed> $assetData
     * @return AbstractAsset
     */
    protected function createAssetByType(string $mediaType, array $assetData) : AbstractAsset
    {
        $normalizedType = strtoupper($mediaType);

        switch ($normalizedType) {
            case 'VIDEO':
                return VideoAsset::getNewInstance($assetData);
            default:
                return ImageAsset::getNewInstance($assetData); // Default to image for unknown types
        }
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
