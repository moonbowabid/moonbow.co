<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs;

use DateTimeImmutable;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

class CreateInventoryAdjustmentInput extends StoreIdRequestInput
{
    /** @var int the change in inventory quantity (positive or negative) */
    public int $delta;

    /** @var string the ID of the location where the adjustment is made */
    public string $locationId;

    /** @var string the SKU ID of the product whose inventory is being adjusted */
    public string $skuId;

    /** @var string the type of count being adjusted {@see InventoryCountType} */
    public string $type;

    /** @var DateTimeImmutable|null the date and time when the adjustment occurred */
    public ?DateTimeImmutable $occurredAt = null;

    /**
     * Constructor.
     *
     * @param array{
     *     storeId: string,
     *     delta: int,
     *     locationId: string,
     *     skuId: string,
     *     type: string,
     *     occurredAt?: DateTimeImmutable|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
