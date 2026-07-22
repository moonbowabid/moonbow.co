<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;

/**
 * Trait for building location request inputs and outputs.
 */
trait CanBuildLocationRequestInputsAndOutputsTrait
{
    /**
     * Converts GraphQL location data to a Location object.
     *
     * @param array<string, mixed> $locationData
     * @return Location
     */
    protected function convertLocationFromGraphQLData(array $locationData) : Location
    {
        $addressData = ArrayHelper::get($locationData, 'address');

        return new Location([
            'active'              => strtoupper(TypeHelper::string(ArrayHelper::get($locationData, 'status'), '')) === 'ACTIVE',
            'address'             => $addressData ? $this->convertAddress(TypeHelper::arrayOfStringsAsKeys($addressData)) : null,
            'inventoryLocationId' => TypeHelper::string(ArrayHelper::get($locationData, 'id'), ''),
            'priority'            => 0, // Default priority as this field isn't in GraphQL response
            'type'                => 'WAREHOUSE', // Default type as this field isn't in GraphQL response
        ]);
    }

    /**
     * Converts GraphQL address data to Address object.
     *
     * @param array<string, mixed>|null $addressData
     * @return Address|null
     */
    protected function convertAddress(?array $addressData) : ?Address
    {
        if (empty($addressData)) {
            return null;
        }

        return new Address([
            'address1'   => TypeHelper::string(ArrayHelper::get($addressData, 'addressLine1'), ''),
            'address2'   => TypeHelper::string(ArrayHelper::get($addressData, 'addressLine2'), ''),
            'city'       => TypeHelper::string(ArrayHelper::get($addressData, 'adminArea2'), ''),
            'state'      => TypeHelper::string(ArrayHelper::get($addressData, 'adminArea1'), ''),
            'postalCode' => TypeHelper::string(ArrayHelper::get($addressData, 'postalCode'), ''),
            'country'    => TypeHelper::string(ArrayHelper::get($addressData, 'countryCode'), ''),
        ]);
    }

    /**
     * Builds the address input for GraphQL.
     *
     * @param Address $address
     * @return array<string, string>
     */
    protected function buildAddressInput(Address $address) : array
    {
        return [
            'addressLine1' => $address->address1,
            'addressLine2' => $address->address2,
            'adminArea2'   => $address->city,
            'adminArea1'   => $address->state,
            'postalCode'   => $address->postalCode,
            'countryCode'  => $address->country,
        ];
    }
}
