<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\LocationsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLocationsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\CreateLocationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\ListLocationsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\ReadLocationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\UpdateLocationRequestAdapter;

/**
 * Gateway for v2 location operations.
 */
class LocationsGateway extends AbstractGateway implements LocationsGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * {@inheritDoc}
     */
    public function createOrUpdate(UpsertLocationInput $input) : Location
    {
        $adapterClass = isset($input->location->inventoryLocationId) && ! empty($input->location->inventoryLocationId)
            ? UpdateLocationRequestAdapter::class
            : CreateLocationRequestAdapter::class;

        /** @var Location $result */
        $result = $this->doAdaptedRequest($adapterClass::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function read(ReadLocationInput $input) : Location
    {
        /** @var Location $result */
        $result = $this->doAdaptedRequest(ReadLocationRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function list(ListLocationsInput $input) : array
    {
        /** @var Location[] $result */
        $result = $this->doAdaptedRequest(ListLocationsRequestAdapter::getNewInstance($input));

        return $result;
    }
}
