<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits;

use GoDaddy\WordPress\MWC\Common\DataObjects\Collection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;

/**
 * Trait for building SKU input data for GraphQL mutations.
 *
 * Note: This trait builds input for CREATE operations with all fields.
 * Update adapters should remove fields not supported by the update schema.
 */
trait CanBuildSkuInputTrait
{
    /**
     * Builds the input object for SKU CREATE mutations.
     * Contains all fields supported by the create schema.
     *
     * @param Sku $sku
     * @return array<string, mixed>
     */
    protected function buildSkuInput(Sku $sku) : array
    {
        $input = [
            'name'                     => $sku->name,
            'label'                    => $sku->label,
            'code'                     => $sku->code,
            'description'              => $sku->description,
            'htmlDescription'          => $sku->htmlDescription,
            'status'                   => $sku->status,
            'disableInventoryTracking' => $sku->disableInventoryTracking,
            'disableShipping'          => $sku->disableShipping,
            'backorderLimit'           => $sku->backorderLimit,
            'upcCode'                  => $sku->upcCode,
            'gtinCode'                 => $sku->gtinCode,
            'weight'                   => $sku->weight,
            'unitOfWeight'             => $sku->unitOfWeight,
            'skuGroupId'               => $sku->skuGroupId,
            'prices'                   => (new Collection($sku->prices))->toArray(),
            'archivedAt'               => $sku->archivedAt,

            // - 'inventoryQuantities' => $sku->inventoryQuantities,

        ];

        // the below properties must be omitted entirely if empty

        $mediaObjects = (new Collection($sku->getMediaObjects()))->toArray();
        if (! empty($mediaObjects)) {
            $input['mediaObjects'] = $mediaObjects;
        }

        $attributeValues = (new Collection($sku->attributeValues))->toArray();
        if (! empty($attributeValues)) {
            $input['attributeValues'] = $attributeValues;
        }

        $metafields = (new Collection($sku->metafields))->toArray();
        if (! empty($metafields)) {
            $input['metafields'] = $metafields;
        }

        return $input;
    }
}
