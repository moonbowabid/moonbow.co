<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

/**
 * Represents the quantity of a SKU in a specific state at a particular time and location.
 */
class InventoryCount extends AbstractDataObject
{
    public string $id;

    /** @var int quantity corresponding to the type -- usually quantity "AVAILABLE" -- this is what we'll show on the front-end as available for purchase */
    public int $quantity;

    /** @var int quantity available on hand (includes committed quantities) -- this is what we'll show in the admin area */
    public int $onHand;

    /** @var string type of inventory count {@see InventoryCountType} */
    public string $type;

    /** @var Sku SKU data from the GraphQL response */
    public Sku $sku;

    /** @var string Location ID from the GraphQL response */
    public string $locationId;

    public string $createdAt;
    public string $updatedAt;

    /**
     * Constructor.
     *
     * @param array{
     *     id: string,
     *     quantity: int,
     *     onHand: int,
     *     type: string,
     *     sku: Sku,
     *     locationId: string,
     *     createdAt: string,
     *     updatedAt: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
