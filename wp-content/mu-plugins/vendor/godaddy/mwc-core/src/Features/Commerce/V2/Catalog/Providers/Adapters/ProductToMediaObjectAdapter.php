<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter to convert Product images to MediaObject references for SKU input.
 */
class ProductToMediaObjectAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts Product images to an array of MediaObject references.
     *
     * @param Product|null $product
     * @return MediaObject[]
     */
    public function convertToSource(?Product $product = null) : array
    {
        if (! $product) {
            return [];
        }

        $mediaObjects = [];

        // Convert main image
        if ($mainImage = $product->getMainImage()) {
            if ($mediaObject = $this->convertImageToMediaObject($mainImage)) {
                $mediaObjects[] = $mediaObject;
            }
        }

        // Convert gallery images
        foreach ($product->getImages() as $image) {
            if ($mediaObject = $this->convertImageToMediaObject($image)) {
                $mediaObjects[] = $mediaObject;
            }
        }

        // Assign positions based on array index
        foreach ($mediaObjects as $index => $mediaObject) {
            $mediaObject->position = $index;
        }

        return $mediaObjects;
    }

    /**
     * Converts a single Image to a MediaObject reference.
     *
     * @param Image $image
     * @return MediaObject|null
     */
    protected function convertImageToMediaObject(Image $image) : ?MediaObject
    {
        try {
            return new MediaObject([
                'type'  => 'IMAGE',
                'url'   => $image->getSize('full')->getUrl(),
                'name'  => $image->getName(),
                'label' => $image->getLabel(),
            ]);
        } catch (Exception $e) {
            // Return null if image conversion fails
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource()
    {
        // no-op
    }
}
