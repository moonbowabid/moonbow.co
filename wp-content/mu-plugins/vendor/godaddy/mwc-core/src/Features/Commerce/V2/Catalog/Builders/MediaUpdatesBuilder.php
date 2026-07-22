<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\MediaUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductMediaObjectsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\HasMediaObjectsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

/**
 * Builder for media relationship updates between products and commerce objects.
 *
 * Handles comparison of product images with commerce object media objects to determine
 * what media objects need to be added or removed during product updates.
 * Works with both SKU and SkuGroup objects.
 */
class MediaUpdatesBuilder
{
    use CanConvertProductMediaObjectsTrait;

    /**
     * Build media updates for a commerce object based on product operation.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param HasMediaObjectsContract $commerceObject
     * @return MediaUpdates|null
     */
    public function build(CreateOrUpdateProductOperationContract $operation, HasMediaObjectsContract $commerceObject) : ?MediaUpdates
    {
        $mediaUpdates = $this->createMediaUpdates();

        if ($toRemove = $this->getMediaToRemove($operation, $commerceObject)) {
            $mediaUpdates->toRemove = $toRemove;
        }

        if ($toAdd = $this->getMediaToAdd($operation, $commerceObject)) {
            $mediaUpdates->toAdd = $toAdd;
        }

        return $mediaUpdates->hasUpdates() ? $mediaUpdates : null;
    }

    /**
     * Create a new MediaUpdates instance.
     *
     * @return MediaUpdates
     */
    protected function createMediaUpdates() : MediaUpdates
    {
        return new MediaUpdates();
    }

    /**
     * Determine which media objects should be removed from the commerce object.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param HasMediaObjectsContract $commerceObject
     * @return string[] Array of media object IDs to remove
     */
    public function getMediaToRemove(CreateOrUpdateProductOperationContract $operation, HasMediaObjectsContract $commerceObject) : array
    {
        // Get current media objects from the commerce object
        $currentMediaObjects = $commerceObject->getMediaObjects();
        if (empty($currentMediaObjects)) {
            return [];
        }

        // Get product image URLs for comparison
        $productImageUrls = $this->getProductImageUrls($operation);

        if (empty($productImageUrls)) {
            // If no product images, remove all current media objects with valid IDs
            $mediaIds = array_map(fn (MediaObject $mediaObj) => $mediaObj->id, $currentMediaObjects);

            return array_values(array_filter($mediaIds));
        }

        // Find media objects that don't have corresponding product images
        $mediaToRemove = [];
        foreach ($currentMediaObjects as $mediaObject) {
            if (! in_array($mediaObject->url, $productImageUrls, true) && $mediaObject->id !== null) {
                $mediaToRemove[] = $mediaObject->id;
            }
        }

        return $mediaToRemove;
    }

    /**
     * Determine which media objects should be added to the commerce object.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param HasMediaObjectsContract $commerceObject
     * @return MediaObject[] Array of MediaObject instances to add
     */
    public function getMediaToAdd(CreateOrUpdateProductOperationContract $operation, HasMediaObjectsContract $commerceObject) : array
    {
        // Convert the local Product into remote media objects
        $localProductMediaObjects = $this->convertProductMediaObjects($operation->getProduct());

        if (empty($localProductMediaObjects)) {
            return [];
        }

        // Get current media object URLs from the commerce object for comparison
        $currentMediaUrls = $this->getCurrentMediaUrls($commerceObject);

        // Check if any product images need to be added
        $mediaToAdd = [];
        foreach ($localProductMediaObjects as $localProductMediaObject) {
            if (! in_array($localProductMediaObject->url, $currentMediaUrls, true)) {
                $mediaToAdd[] = $localProductMediaObject;
            }
        }

        return $mediaToAdd;
    }

    /**
     * Extract product image URLs for comparison.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return string[]
     */
    protected function getProductImageUrls(CreateOrUpdateProductOperationContract $operation) : array
    {
        $product = $operation->getProduct();

        /** @var Image[] $productImages */
        $productImages = array_values(array_filter(array_merge(
            [$product->getMainImage()],
            $product->getImages()
        )));

        return array_values(array_filter(array_map(function (Image $image) {
            try {
                return $image->getSize('full')->getUrl();
            } catch(Exception $e) {
                return null;
            }
        }, $productImages)));
    }

    /**
     * Extract current media object URLs from commerce object.
     *
     * @param HasMediaObjectsContract $commerceObject
     * @return string[]
     */
    protected function getCurrentMediaUrls(HasMediaObjectsContract $commerceObject) : array
    {
        /** @var MediaObject[] $currentMediaObjects */
        $currentMediaObjects = $commerceObject->getMediaObjects();

        return array_map(fn (MediaObject $mediaObj) => $mediaObj->url, $currentMediaObjects);
    }
}
