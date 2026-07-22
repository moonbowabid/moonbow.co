<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\Locations\Traits\CanBuildLocationRequestInputsAndOutputsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries\ReadLocationOperation;

/**
 * Adapts a read location request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(ReadLocationInput $input)
 * @property ReadLocationInput $input
 */
class ReadLocationRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildLocationRequestInputsAndOutputsTrait;

    /**
     * ReadLocationRequestAdapter constructor.
     *
     * @param ReadLocationInput $input
     */
    public function __construct(ReadLocationInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ReadLocationOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id' => $this->input->locationId,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return Location
     */
    protected function convertResponse(ResponseContract $response) : Location
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $locationData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.location', []));

        return $this->convertLocationFromGraphQLData($locationData);
    }
}
