<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Trait for creating SkuGroup objects from GraphQL response data.
 */
trait CanCreateSkuGroupFromResponseTrait
{
    use CanConvertResponseToAttributeTrait;
    use CanCreateListObjectFromResponseTrait;
    use CanGetOptionalValuesFromResponseTrait;

    /**
     * Creates a SkuGroup object from response data.
     *
     * @param array<string, mixed> $skuGroupData
     * @return SkuGroup
     */
    protected function createSkuGroupFromResponse(array $skuGroupData) : SkuGroup
    {
        return new SkuGroup([
            'id'              => TypeHelper::string(ArrayHelper::get($skuGroupData, 'id'), ''),
            'name'            => TypeHelper::string(ArrayHelper::get($skuGroupData, 'name'), ''),
            'label'           => TypeHelper::string(ArrayHelper::get($skuGroupData, 'label'), ''),
            'description'     => $this->getOptionalStringFromResponse($skuGroupData, 'description'),
            'htmlDescription' => $this->getOptionalStringFromResponse($skuGroupData, 'htmlDescription'),
            'type'            => TypeHelper::string(ArrayHelper::get($skuGroupData, 'type'), 'PHYSICAL'),
            'status'          => TypeHelper::string(ArrayHelper::get($skuGroupData, 'status'), 'DRAFT'),
            'createdAt'       => $this->getOptionalStringFromResponse($skuGroupData, 'createdAt'),
            'updatedAt'       => $this->getOptionalStringFromResponse($skuGroupData, 'updatedAt'),
            'archivedAt'      => $this->getOptionalStringFromResponse($skuGroupData, 'archivedAt'),
            'skus'            => [], // Related SKUs would be populated separately when needed
            'mediaObjects'    => $this->createMediaObjectsFromResponse($skuGroupData),
            'channels'        => $this->createChannelsFromResponse($skuGroupData),
            'lists'           => $this->createListsFromResponse($skuGroupData),
            'attributes'      => $this->convertAttributes(
                GraphQLHelper::extractGraphQLEdges($skuGroupData, 'attributes')
            ),
        ]);
    }

    /**
     * Creates MediaObject instances from GraphQL response data.
     *
     * @param array<string, mixed> $skuGroupData
     * @return MediaObject[]
     */
    protected function createMediaObjectsFromResponse(array $skuGroupData) : array
    {
        $mediaObjects = [];

        $nodes = GraphQLHelper::extractGraphQLEdges($skuGroupData, 'mediaObjects');

        foreach ($nodes as $nodeData) {
            if (! empty($nodeData)) {
                $mediaObjects[] = new MediaObject([
                    'id'       => TypeHelper::string(ArrayHelper::get($nodeData, 'id'), ''),
                    'name'     => TypeHelper::string(ArrayHelper::get($nodeData, 'name'), ''),
                    'label'    => TypeHelper::string(ArrayHelper::get($nodeData, 'label'), ''),
                    'type'     => TypeHelper::string(ArrayHelper::get($nodeData, 'type'), 'IMAGE'),
                    'url'      => TypeHelper::string(ArrayHelper::get($nodeData, 'url'), ''),
                    'position' => TypeHelper::int(ArrayHelper::get($nodeData, 'position'), 0),
                ]);
            }
        }

        return $mediaObjects;
    }

    /**
     * Creates Channel instances from GraphQL response data.
     *
     * @param array<string, mixed> $skuGroupData
     * @return Channel[]
     */
    protected function createChannelsFromResponse(array $skuGroupData) : array
    {
        $channels = [];

        $nodes = GraphQLHelper::extractGraphQLEdges($skuGroupData, 'channels');

        foreach ($nodes as $nodeData) {
            if (! empty($nodeData)) {
                $channels[] = new Channel([
                    'channelId' => TypeHelper::string(ArrayHelper::get($nodeData, 'channelId'), ''),
                ]);
            }
        }

        return $channels;
    }

    /**
     * Creates ListObject instances from GraphQL response data.
     *
     * @param array<string, mixed> $skuGroupData
     * @return ListObject[]
     */
    protected function createListsFromResponse(array $skuGroupData) : array
    {
        $lists = [];

        $nodes = GraphQLHelper::extractGraphQLEdges($skuGroupData, 'lists');

        foreach ($nodes as $nodeData) {
            /** @var array<string, mixed> $nodeData */
            if (! empty($nodeData)) {
                $lists[] = $this->createListObjectFromResponse(TypeHelper::arrayOfStringsAsKeys($nodeData));
            }
        }

        return $lists;
    }
}
