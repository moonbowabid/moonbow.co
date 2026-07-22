<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\HasMediaObjectsContract;

/**
 * Represents a SKU Group (Product Parent) in the Commerce Catalog v2 API.
 */
class SkuGroup extends AbstractDataObject implements HasMediaObjectsContract
{
    /** @var string|null Globally-unique ID */
    public ?string $id = null;

    /** @var string Unique human-friendly identifier */
    public string $name;

    /** @var string Display label */
    public string $label;

    /** @var string|null Merchant defined description */
    public ?string $description = null;

    /** @var string|null HTML description for rich text content */
    public ?string $htmlDescription = null;

    /** @var string Type of SKU Group - typically PHYSICAL or DIGITAL */
    public string $type = 'PHYSICAL';

    /** @var string Status - DRAFT, ACTIVE, or ARCHIVED */
    public string $status = 'DRAFT';

    /** @var string|null Creation timestamp */
    public ?string $createdAt = null;

    /** @var string|null Last update timestamp */
    public ?string $updatedAt = null;

    /** @var string|null Archive timestamp */
    public ?string $archivedAt = null;

    /** @var Sku[] Related SKUs in this group */
    public array $skus = [];

    /** @var MediaObject[] Images, videos, files */
    public array $mediaObjects = [];

    /** @var Channel[] Channels where this SKU Group is available */
    public array $channels = [];

    /** @var Attribute[] Attributes defining options for this SKU Group */
    public array $attributes = [];

    /** @var ListObject[] Lists that this SKU Group belongs to */
    public array $lists = [];

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
     * Creates a new SKU Group data object.
     *
     * @param array{
     *     id?: string|null,
     *     name: string,
     *     label: string,
     *     description?: string|null,
     *     htmlDescription?: string|null,
     *     type?: string,
     *     status?: string,
     *     createdAt?: string|null,
     *     updatedAt?: string|null,
     *     archivedAt?: string|null,
     *     skus?: Sku[],
     *     mediaObjects?: MediaObject[],
     *     channels?: Channel[],
     *     attributes?: Attribute[],
     *     lists?: ListObject[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
