<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

/**
 * Represents a SKU price for the Commerce Catalog v2 API.
 * Maps to CreateSKUPriceInput in GraphQL schema.
 */
class SkuPrice extends AbstractDataObject
{
    /** @var string|null Globally-unique ID (null for new prices) */
    public ?string $id = null;

    /** @var SimpleMoney The main price value */
    public SimpleMoney $value;

    /** @var SimpleMoney|null Optional compare-at price (original price before discount) */
    public ?SimpleMoney $compareAtValue = null;

    /**
     * Creates a new SKU Price data object.
     *
     * @param array{
     *     id?: string|null,
     *     value: SimpleMoney,
     *     compareAtValue?: SimpleMoney|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
