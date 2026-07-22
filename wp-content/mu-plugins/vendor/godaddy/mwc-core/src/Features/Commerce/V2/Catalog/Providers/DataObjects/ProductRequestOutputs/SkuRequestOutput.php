<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Data object representing the output (response) from a SKU create/update request, including both the SKU and its associated SKU Group.
 */
class SkuRequestOutput extends AbstractDataObject
{
    public SkuGroup $skuGroup;
    public Sku $sku;

    /**
     * @param array{
     *     skuGroup: SkuGroup,
     *     sku: Sku
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
