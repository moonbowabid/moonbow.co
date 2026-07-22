<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Contains remote references need to map local product.
 */
class ProductReferences extends AbstractDataObject
{
    /** @var string the v2 UUID for the product's SKU */
    public string $skuId;

    /** @var string the v2 UUID for the product's SKU group */
    public string $skuGroupId = '';

    /** @var string the product SKU */
    public string $skuCode;

    /** @var Reference[] */
    public array $skuReferences;

    /** @var Reference[] */
    public array $skuGroupReferences;

    /** @var MediaObject[] */
    public array $mediaObjects;

    /** @var Sku|null Full SKU data object with prices, weight, inventory settings */
    public ?Sku $sku = null;

    /** @var SkuGroup|null Full SKU Group data object with product type, lists, attributes */
    public ?SkuGroup $skuGroup = null;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     skuId: string,
     *     skuCode: string,
     *     skuReferences: Reference[],
     *     skuGroupReferences: Reference[],
     *     mediaObjects: MediaObject[],
     *     sku?: Sku|null,
     *     skuGroup?: SkuGroup|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
