<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Represents a single list tree node mapping result from the API.
 */
class ListTreeNodeMapping extends AbstractDataObject
{
    public string $listTreeNodeId;

    public string $listId;

    public string $listTreeId;

    public ?string $parentListTreeNodeId;

    /**
     * @param array{
     *     listTreeNodeId: string,
     *     listId: string,
     *     listTreeId: string,
     *     parentListTreeNodeId?: string|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct(array_merge([
            'parentListTreeNodeId' => null,
        ], $data));
    }
}
