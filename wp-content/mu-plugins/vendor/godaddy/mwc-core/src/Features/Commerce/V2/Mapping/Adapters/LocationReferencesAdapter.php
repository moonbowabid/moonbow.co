<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\ReferencesAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferences;

/**
 * Adapter to convert individual location references data from GraphQL response.
 *
 * Important: The source should be an individual location node, not the full GraphQL response.
 */
class LocationReferencesAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> */
    protected array $source;

    /**
     * LocationReferencesAdapter constructor.
     *
     * @param array<string, mixed> $source Individual location node from GraphQL response
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts from individual location node to LocationReferences.
     */
    public function convertFromSource() : LocationReferences
    {
        return new LocationReferences([
            'locationId'         => TypeHelper::string(ArrayHelper::get($this->source, 'id'), ''),
            'locationName'       => TypeHelper::string(ArrayHelper::get($this->source, 'name'), ''),
            'locationLabel'      => TypeHelper::string(ArrayHelper::get($this->source, 'label'), ''),
            'locationStatus'     => TypeHelper::string(ArrayHelper::get($this->source, 'status'), ''),
            'locationReferences' => $this->extractLocationReferences(),
        ]);
    }

    /**
     * Converts to GraphQL format (not implemented).
     *
     * @return array<string, mixed>
     */
    public function convertToSource() : array
    {
        return [];
    }

    /**
     * Extracts location references from the current location node.
     *
     * @return Reference[]
     */
    protected function extractLocationReferences() : array
    {
        return ReferencesAdapter::getNewInstance($this->source)->convertFromSource();
    }
}
