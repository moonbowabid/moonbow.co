<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

/**
 * Represents an inventory adjustment record.
 */
class InventoryAdjustment extends AbstractDataObject
{
    /** @var string unique UUID of the adjustment */
    public string $id;

    /** @var int the change in inventory quantity (positive or negative) */
    public int $delta;

    /** @var string the type of count that was adjusted {@see InventoryCountType} */
    public string $type;

    /** @var string ISO 8601 timestamp of when the adjustment occurred */
    public string $occurredAt;

    /** @var Sku the SKU associated with the adjustment */
    public Sku $sku;

    /** @var string the ID of the location where the adjustment occurred */
    public string $locationId;

    /** @var Reference[] references to other resources */
    public array $references = [];

    /**
     * Constructor.
     *
     * @param array{
     *     id: string,
     *     delta: int,
     *     type: string,
     *     occurredAt: string,
     *     sku: Sku,
     *     locationId: string,
     *     references?: array<Reference>
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
