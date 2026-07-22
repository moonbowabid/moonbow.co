<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Data object representing the output (response) from a SKU Group create/update request.
 */
class SkuGroupRequestOutput extends AbstractDataObject
{
    public SkuGroup $skuGroup;

    /** @var Sku[] */
    public array $skus = [];

    /**
     * @param array{
     *     skuGroup: SkuGroup,
     *     skus?: Sku[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
