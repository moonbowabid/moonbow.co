<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\HasMediaObjectsContract;

/**
 * Represents a SKU (Stock Keeping Unit) in the Commerce Catalog v2 API.
 */
class Sku extends AbstractDataObject implements HasMediaObjectsContract
{
    /** @var string|null Globally-unique ID */
    public ?string $id = null;

    /** @var string Unique name (equivalent to slug in WooCommerce) */
    public string $name;

    /** @var string Display label */
    public string $label;

    /** @var string Unique SKU code */
    public string $code;

    /** @var string|null Description text */
    public ?string $description = null;

    /** @var string|null HTML description for rich text */
    public ?string $htmlDescription = null;

    /** @var string Status - DRAFT, ACTIVE, or ARCHIVED */
    public string $status = 'DRAFT';

    /** @var string|null Creation timestamp */
    public ?string $createdAt = null;

    /** @var string|null Last update timestamp */
    public ?string $updatedAt = null;

    /** @var string|null Archive timestamp */
    public ?string $archivedAt = null;

    /** @var string|null EAN code */
    public ?string $eanCode = null;

    /** @var string|null Global Trade Item Number code */
    public ?string $gtinCode = null;

    /** @var string|null Universal Product Code */
    public ?string $upcCode = null;

    /** @var float|null Weight of the SKU */
    public ?float $weight = null;

    /** @var string|null Unit of weight - KG, GR, LB, or OZ */
    public ?string $unitOfWeight = null;

    /** @var bool Whether to track stock */
    public bool $disableInventoryTracking = false;

    /** @var bool Whether the SKU should be shipped */
    public bool $disableShipping = false;

    /** @var int|null Number of backorders allowed (null = unlimited) */
    public ?int $backorderLimit = null;

    /** @var string Parent SKU group ID */
    public string $skuGroupId;

    /** @var Location[] Locations where SKU is available */
    public array $locations = [];

    /** @var AttributeValue[] Specific attribute values (color: red, size: large) */
    public array $attributeValues = [];

    /** @var Attribute[] All attributes from parent group */
    public array $attributes = [];

    /** @var MediaObject[] Images, videos, files */
    public array $mediaObjects = [];

    /** @var SkuPrice[] All prices the SKU can be sold at */
    public array $prices = [];

    /** @var Metafield[] Metafields from the platform (e.g., brand, condition, dimensions) */
    public array $metafields = [];

    /**
     * Gets the media objects.
     *
     * @return MediaObject[]
     */
    public function getMediaObjects() : array
    {
        return $this->mediaObjects;
    }

    /**
     * Creates a new SKU data object.
     *
     * @param array{
     *     id?: string|null,
     *     name: string,
     *     label: string,
     *     code: string,
     *     description?: string|null,
     *     htmlDescription?: string|null,
     *     status?: string,
     *     createdAt?: string|null,
     *     updatedAt?: string|null,
     *     archivedAt?: string|null,
     *     eanCode?: string|null,
     *     gtinCode?: string|null,
     *     upcCode?: string|null,
     *     weight?: float|null,
     *     unitOfWeight?: string|null,
     *     disableInventoryTracking?: bool,
     *     disableShipping?: bool,
     *     backorderLimit?: int|null,
     *     skuGroupId: string,
     *     locations?: Location[],
     *     attributeValues?: AttributeValue[],
     *     attributes?: Attribute[],
     *     mediaObjects?: MediaObject[],
     *     prices?: SkuPrice[],
     *     metafields?: Metafield[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
