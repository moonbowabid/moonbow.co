<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * List tree node data object representing a single node in a hierarchical list structure.
 */
class ListTreeNode extends AbstractDataObject
{
    /** @var ?string the globally-unique ID of the list node */
    public ?string $id = null;

    /** @var ?int the position of the node in the list */
    public ?int $position = null;

    /** @var ?string the creation date of the list node */
    public ?string $createdAt = null;

    /** @var ?string the last update date of the list node */
    public ?string $updatedAt = null;

    /** @var ?ListObject the list associated with the node */
    public ?ListObject $list = null;

    /** @var ?string the ID of the list associated with the node */
    public ?string $listId = null;

    /** @var ?ListTree the List Tree associated with the node */
    public ?ListTree $listTree = null;

    /** @var ?string the ID of the List Tree associated with the node */
    public ?string $listTreeId = null;

    /** @var ?ListTreeNode the parent node of the list node */
    public ?ListTreeNode $parentListTreeNode = null;

    /** @var ?string the ID of the parent node */
    public ?string $parentListTreeNodeId = null;

    /** @var ?ListTreeNode[] array of children nodes */
    public ?array $listTreeNodes = null;

    /** @var ?Reference[] array of references to external services */
    public ?array $references = null;

    /**
     * List tree node data object constructor.
     *
     * @param array{
     *     id?: ?string,
     *     position?: ?int,
     *     createdAt?: ?string,
     *     updatedAt?: ?string,
     *     list?: ?ListObject,
     *     listId?: ?string,
     *     listTree?: ?ListTree,
     *     listTreeId?: ?string,
     *     parentListTreeNode?: ?ListTreeNode,
     *     parentListTreeNodeId?: ?string,
     *     listTreeNodes?: ?ListTreeNode[],
     *     references?: ?Reference[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
