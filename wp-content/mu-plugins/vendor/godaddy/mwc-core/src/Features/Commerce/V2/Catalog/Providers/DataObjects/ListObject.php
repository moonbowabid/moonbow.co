<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * List data object representing a group of SKU groups organized by similar characteristics.
 */
class ListObject extends AbstractDataObject
{
    /** @var ?string the globally-unique ID of the List */
    public ?string $id = null;

    /** @var string the display label of the List */
    public string $label;

    /** @var string a unique name for the List */
    public string $name;

    /** @var string|null a single-line textual description of the List */
    public ?string $description = null;

    /** @var string|null HTML description for the List */
    public ?string $htmlDescription = null;

    /** @var string|null the status of the list, one of `ACTIVE`, `DRAFT`, `ARCHIVED` */
    public ?string $status = null;

    /** @var ?string the date and time of when the List was created */
    public ?string $createdAt = null;

    /** @var ?string the date and time of when the List was updated */
    public ?string $updatedAt = null;

    /** @var ?Reference[] array of references to external services */
    public ?array $references = null;

    /**
     * List data object constructor.
     *
     * @param array{
     *     id?: ?string,
     *     label: string,
     *     name: string,
     *     description?: ?string,
     *     htmlDescription?: ?string,
     *     status?: ?string,
     *     createdAt?: ?string,
     *     updatedAt?: ?string,
     *     references?: ?Reference[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
