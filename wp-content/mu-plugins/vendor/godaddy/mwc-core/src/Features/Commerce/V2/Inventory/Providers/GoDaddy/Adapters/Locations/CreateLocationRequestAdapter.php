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
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations\CreateLocationOperation;

/**
 * Adapts a create location request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(UpsertLocationInput $input)
 * @property UpsertLocationInput $input
 */
class CreateLocationRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildLocationRequestInputsAndOutputsTrait;

    /**
     * CreateLocationRequestAdapter constructor.
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
        return (new CreateLocationOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'input' => $this->buildCreateLocationInput(),
        ];
    }

    /**
     * Builds the input object for the CreateLocation mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildCreateLocationInput() : array
    {
        $locationInput = [
            'label'  => 'WooCommerce Store',
            'name'   => 'woocommerce-store',
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
        $locationData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.createLocation', []));

        return $this->convertLocationFromGraphQLData($locationData);
    }
}
