<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AbstractPaginatedOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\Contracts\ReferencesOutputContract;

/**
 * Output data object for Location references query response.
 */
class LocationReferencesOutput extends AbstractPaginatedOutput implements ReferencesOutputContract
{
    /** @var LocationReferences[] list of location references */
    public array $locationReferences;

    /**
     * LocationReferencesOutput constructor.
     *
     * @param array{
     *     hasNextPage: bool,
     *     locationReferences: LocationReferences[],
     *     endCursor?: string|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
