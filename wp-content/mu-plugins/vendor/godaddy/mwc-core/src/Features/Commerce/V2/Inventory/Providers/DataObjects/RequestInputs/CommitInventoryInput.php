<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

class CommitInventoryInput extends StoreIdRequestInput
{
    /** @var string the SKU ID of the product whose inventory is being committed */
    public string $skuId;

    /** @var string the ID of the location where the commitment is made */
    public string $locationId;

    /** @var int the quantity to commit */
    public int $quantity;

    /** @var bool whether backorders are allowed for this commitment */
    public bool $allowBackorders;

    /** @var Reference[] array of references for tracking the commitment */
    public array $references;

    /**
     * Constructor.
     *
     * @param array{
     *     storeId: string,
     *     skuId: string,
     *     locationId: string,
     *     quantity: int,
     *     allowBackorders: bool,
     *     references: Reference[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
