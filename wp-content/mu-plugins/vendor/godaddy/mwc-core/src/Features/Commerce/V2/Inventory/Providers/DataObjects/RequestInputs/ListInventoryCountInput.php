<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

/**
 * List inventory count input.
 */
class ListInventoryCountInput extends StoreIdRequestInput
{
    /** @var string[] */
    public array $skuIds;

    /** @var string|null Location ID for client-side filtering -- omitting this will return all locations */
    public ?string $locationId = null;

    /** @var string|null {@see InventoryCountType} -- omitting this will return all types */
    public ?string $type;

    /**
     * @param array{
     *     storeId: string,
     *     skuIds: string[],
     *     locationId?: string|null,
     *     type?: string|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
