<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\Traits;

use GoDaddy\WordPress\MWC\Common\DataObjects\Collection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Trait for building SKU Group input data for GraphQL mutations.
 *
 * Note: This trait builds input for CREATE operations with all fields.
 *
 * UPDATE operations only support: name, label, description, htmlDescription, status, metafields
 */
trait CanBuildSkuGroupInputTrait
{
    /**
     * Builds the input object for SKU Group CREATE mutations.
     * Contains all fields supported by the create schema.
     *
     * @param SkuGroup $skuGroup
     * @return array<string, mixed>
     */
    protected function buildSkuGroupInput(SkuGroup $skuGroup) : array
    {
        $input = [
            'name'            => $skuGroup->name,
            'label'           => $skuGroup->label,
            'description'     => $skuGroup->description,
            'htmlDescription' => $skuGroup->htmlDescription,
            'status'          => $skuGroup->status,
            'type'            => $skuGroup->type,
            'archivedAt'      => $skuGroup->archivedAt,
        ];

        // the below properties must be omitted entirely if empty
        $mediaObjects = (new Collection($skuGroup->getMediaObjects()))->toArray();
        if (! empty($mediaObjects)) {
            $input['mediaObjects'] = $mediaObjects;
        }

        $channels = (new Collection($skuGroup->channels))->toArray();
        if (! empty($channels)) {
            $input['channels'] = $channels;
        }

        if (! empty($skuGroup->attributes)) {
            $input['attributes'] = (new Collection($skuGroup->attributes))->toArray();
        }

        return $input;
    }
}
