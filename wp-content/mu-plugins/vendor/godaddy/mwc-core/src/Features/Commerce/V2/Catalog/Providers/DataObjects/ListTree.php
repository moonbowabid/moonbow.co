<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * List tree data object representing hierarchical information of a group of lists.
 */
class ListTree extends AbstractDataObject
{
    /** @var ?string the globally-unique ID of the list tree */
    public ?string $id = null;

    /** @var ?string the display label of the list tree */
    public ?string $label = null;

    /** @var ?string a unique name of the list tree */
    public ?string $name = null;

    /** @var ?string the description of the list tree */
    public ?string $description = null;

    /** @var ?string HTML description for the List Tree */
    public ?string $htmlDescription = null;

    /** @var ?string the status of the list tree, one of `ACTIVE`, `ARCHIVED`, `DRAFT` */
    public ?string $status = null;

    /** @var ?string the timestamp of when the list tree was activated */
    public ?string $activatedAt = null;

    /** @var ?string the timestamp of when the list tree was archived */
    public ?string $archivedAt = null;

    /** @var ?string the creation date of the list tree */
    public ?string $createdAt = null;

    /** @var ?string the last update date of the list tree */
    public ?string $updatedAt = null;

    /** @var ?ListTreeNode[] array of list tree nodes */
    public ?array $listTreeNodes = null;

    /** @var ?array<mixed> array of metafields */
    public ?array $metafields = null;

    /** @var ?Reference[] array of references to external services */
    public ?array $references = null;

    /**
     * List tree data object constructor.
     *
     * @param array{
     *     id?: ?string,
     *     label?: ?string,
     *     name?: ?string,
     *     description?: ?string,
     *     htmlDescription?: ?string,
     *     status?: ?string,
     *     activatedAt?: ?string,
     *     archivedAt?: ?string,
     *     createdAt?: ?string,
     *     updatedAt?: ?string,
     *     listTreeNodes?: ?ListTreeNode[],
     *     metafields?: ?array<mixed>,
     *     references?: ?Reference[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
