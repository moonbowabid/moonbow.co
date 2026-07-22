<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLocationsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\Traits\CanBuildLocationRequestInputsAndOutputsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries\ListLocationsOperation;

/**
 * Adapts a list locations request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(ListLocationsInput $input)
 * @property ListLocationsInput $input
 */
class ListLocationsRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildLocationRequestInputsAndOutputsTrait;

    /**
     * ListLocationsRequestAdapter constructor.
     *
     * @param ListLocationsInput $input
     */
    public function __construct(ListLocationsInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ListLocationsOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'first' => 100, // Default limit for now; we don't currently expect there to be very many records
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return Location[]
     */
    protected function convertResponse(ResponseContract $response) : array
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $locationNodes = GraphQLHelper::extractGraphQLEdges($responseBody, 'data.locations');

        $locations = [];
        foreach ($locationNodes as $locationNode) {
            $locationData = TypeHelper::arrayOfStringsAsKeys($locationNode);
            $locations[] = $this->convertLocationFromGraphQLData($locationData);
        }

        return $locations;
    }
}
