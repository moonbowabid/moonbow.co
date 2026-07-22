<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Represents a location where inventory is tracked.
 */
class Location extends AbstractDataObject
{
    /** @var string Globally-unique ID */
    public string $id;

    /** @var string Display name of the location */
    public string $label;

    /** @var string Unique and human-friendly name */
    public string $name;

    /** @var string Status - ACTIVE, INACTIVE, or ARCHIVED */
    public string $status = 'ACTIVE';

    /** @var string Creation timestamp */
    public string $createdAt;

    /** @var string Last update timestamp */
    public string $updatedAt;

    /** @var Address|null The address of the location */
    public ?Address $address = null;

    /** @var Reference[] External service integrations */
    public array $references = [];

    /** @var Sku[] SKUs available at this location */
    public array $skus = [];

    /**
     * Creates a new location data object.
     *
     * @param array{
     *     id: string,
     *     label: string,
     *     name: string,
     *     status?: string,
     *     createdAt: string,
     *     updatedAt: string,
     *     address?: mixed,
     *     references?: Reference[],
     *     skus?: Sku[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
