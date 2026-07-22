<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;

class LocationReferences extends AbstractDataObject
{
    /** @var string the v2 UUID for the location */
    public string $locationId;

    /** @var string the location name */
    public string $locationName;

    /** @var string the location display label */
    public string $locationLabel;

    /** @var string the location status */
    public string $locationStatus;

    /** @var Reference[] */
    public array $locationReferences;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     locationId: string,
     *     locationName: string,
     *     locationLabel: string,
     *     locationStatus: string,
     *     locationReferences: Reference[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
