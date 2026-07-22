<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Metafield;

/**
 * Data object representing a SKU within the context of an {@see InventoryCount}.
 * We don't request the full SKU data from the API, just a subset of fields. That's why we don't use the full catalog
 * {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku} object.
 */
class Sku extends AbstractDataObject
{
    /** @var string unique sku ID */
    public string $id;

    /** @var int|null backorder limit for the SKU, if any */
    public ?int $backorderLimit = null;

    /** @var Metafield[] metafields associated with the SKU */
    public array $metafields = [];

    /**
     * Constructor.
     *
     * @param array{
     *     id: string,
     *     backorderLimit?: int|null,
     *     metafields?: Metafield[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
