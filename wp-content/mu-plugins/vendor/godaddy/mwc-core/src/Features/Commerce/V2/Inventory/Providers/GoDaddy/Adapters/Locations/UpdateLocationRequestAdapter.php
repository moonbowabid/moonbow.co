<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\Traits\CanBuildLocationRequestInputsAndOutputsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateLocationOperation;

/**
 * Adapts an update location request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(UpsertLocationInput $input)
 * @property UpsertLocationInput $input
 */
class UpdateLocationRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildLocationRequestInputsAndOutputsTrait;

    /**
     * UpdateLocationRequestAdapter constructor.
     *
     * @param UpsertLocationInput $input
     */
    public function __construct(UpsertLocationInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateLocationOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->location->inventoryLocationId,
            'input' => $this->buildUpdateLocationInput(),
        ];
    }

    /**
     * Builds the input object for the UpdateLocation mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildUpdateLocationInput() : array
    {
        $locationInput = [
            'status' => $this->input->location->active ? 'ACTIVE' : 'INACTIVE',
        ];

        // Add address if provided
        if ($this->input->location->address) {
            $locationInput['address'] = $this->buildAddressInput($this->input->location->address);
        }

        return $locationInput;
    }

    /**
     * {@inheritDoc}
     *
     * @return Location
     */
    protected function convertResponse(ResponseContract $response) : Location
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $locationData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.updateLocation', []));

        return $this->convertLocationFromGraphQLData($locationData);
    }
}
