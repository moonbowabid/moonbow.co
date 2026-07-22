<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for archiving a SKU Group.
 */
class ArchiveSkuGroupInput extends StoreIdRequestInput
{
    /** @var string remote SKU Group UUID to archive */
    public string $skuGroupId;

    /** @var bool whether to cascade-archive every SKU in the group */
    public bool $cascadeSkus = false;

    /**
     * @param array{
     *     skuGroupId: string,
     *     storeId: string,
     *     cascadeSkus?: bool
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
