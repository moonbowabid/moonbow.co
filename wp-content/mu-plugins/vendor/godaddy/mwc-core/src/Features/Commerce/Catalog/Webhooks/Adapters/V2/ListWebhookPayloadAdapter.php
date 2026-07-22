<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Adapters\V2;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\InsertLocalCategoryService;

/**
 * Adapter for converting list webhook payloads into Category objects.
 * We go straight to Category objects here instead of Lists in order to use services like {@see InsertLocalCategoryService}.
 */
class ListWebhookPayloadAdapter
{
    use CanGetNewInstanceTrait;

    /**
     * Converts the list webhook payload into a {@see Category} object.
     *
     * @param array<mixed> $payload
     * @param ?ListTreeNode $listTreeNode
     * @return Category
     * @throws MissingCategoryRemoteIdException
     */
    public function convertResponse(array $payload, ?ListTreeNode $listTreeNode = null) : Category
    {
        return new Category([
            'altId'       => TypeHelper::stringOrNull(ArrayHelper::get($payload, 'name')),
            'categoryId'  => $this->getValidCategoryId($payload, 'id'),
            'createdAt'   => TypeHelper::stringOrNull(ArrayHelper::get($payload, 'createdAt')),
            'depth'       => $this->adaptDepth($listTreeNode),
            'description' => $this->adaptDescription($payload),
            'name'        => TypeHelper::string(ArrayHelper::get($payload, 'label'), ''),
            'parentId'    => $this->adaptParentId($listTreeNode),
            'updatedAt'   => TypeHelper::stringOrNull(ArrayHelper::get($payload, 'updatedAt')),
            'deletedAt'   => TypeHelper::stringOrNull(ArrayHelper::get($payload, 'archivedAt')),
        ]);
    }

    /**
     * Resolves the parent category's remote List ID from the ListTreeNode hierarchy.
     *
     * @param ?ListTreeNode $listTreeNode
     * @return ?string
     */
    protected function adaptParentId(?ListTreeNode $listTreeNode) : ?string
    {
        if (! $listTreeNode || ! $listTreeNode->parentListTreeNode || ! $listTreeNode->parentListTreeNode->list) {
            return null;
        }

        return $listTreeNode->parentListTreeNode->list->id;
    }

    /**
     * Calculates the depth by counting ancestor levels in the ListTreeNode hierarchy.
     *
     * @param ?ListTreeNode $listTreeNode
     * @return int
     */
    protected function adaptDepth(?ListTreeNode $listTreeNode) : int
    {
        $depth = 0;
        $currentNode = $listTreeNode ? $listTreeNode->parentListTreeNode : null;

        while ($currentNode !== null) {
            $depth++;
            $currentNode = $currentNode->parentListTreeNode;
        }

        return $depth;
    }

    /**
     * Returns a valid category ID from the response data, or throws an exception if not found.
     *
     * @param array<mixed> $responseData
     * @param string $key
     * @return string
     * @throws MissingCategoryRemoteIdException
     */
    protected function getValidCategoryId(array $responseData, string $key) : string
    {
        $value = TypeHelper::string(ArrayHelper::get($responseData, $key), '');

        if (! $value) {
            throw new MissingCategoryRemoteIdException('Category ID is missing from the webhook payload.');
        }

        return $value;
    }

    /**
     * Adapts the description.
     *
     * This prioritizes the htmlDescription over plain text. Important here is to maintain the same prioritization as
     * Commerce Home to ensure an expected experience.
     *
     * @param array<mixed> $payload
     * @return string|null
     */
    protected function adaptDescription(array $payload) : ?string
    {
        $htmlDescription = TypeHelper::stringOrNull(ArrayHelper::get($payload, 'htmlDescription'));
        if ($htmlDescription) {
            return $htmlDescription;
        }

        return TypeHelper::stringOrNull(ArrayHelper::get($payload, 'description'));
    }
}
