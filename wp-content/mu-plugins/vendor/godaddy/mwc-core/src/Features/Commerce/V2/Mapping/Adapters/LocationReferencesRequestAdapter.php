<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\LocationsReferencesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferencesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferencesOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Http\GraphQL\Requests\Request;

/**
 * Request adapter for {@see LocationsReferencesOperation}.
 *
 * @method static static getNewInstance(LocationReferencesInput $input)
 */
class LocationReferencesRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected LocationReferencesInput $input;

    /**
     * LocationReferencesRequestAdapter constructor.
     *
     * @param LocationReferencesInput $input
     */
    public function __construct(LocationReferencesInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts from source input to GraphQL request.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth($this->getGraphQLOperation())
            ->setStoreId($this->input->storeId)
            ->setMethod('post');
    }

    /**
     * Gets the GraphQL operation for this request.
     *
     * @return GraphQLOperationContract
     */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new LocationsReferencesOperation())->setVariables($this->getQueryVariables());
    }

    /**
     * Gets query variables for the GraphQL operation.
     *
     * @return array<string, mixed>
     */
    protected function getQueryVariables() : array
    {
        return [
            'first'           => $this->input->perPage,
            'after'           => $this->input->cursor,
            'referenceValues' => $this->input->referenceValues,
        ];
    }

    /**
     * Converts GraphQL response to output object.
     *
     * @param ResponseContract $response
     * @return LocationReferencesOutput
     */
    protected function convertResponse(ResponseContract $response) : LocationReferencesOutput
    {
        $responseBody = $response->getBody();

        // Extract location nodes using GraphQLHelper
        $locationNodes = GraphQLHelper::extractGraphQLEdges(TypeHelper::arrayOfStringsAsKeys($responseBody), 'data.locations');

        // Convert each location node to LocationReferences
        $locationReferences = array_map(function ($locationNode) {
            /** @var array<string, mixed> $locationNode */
            $adapter = new LocationReferencesAdapter($locationNode);

            return $adapter->convertFromSource();
        }, $locationNodes);

        // Extract pagination info
        $pageInfo = ArrayHelper::get($responseBody, 'data.locations.pageInfo', []);

        return new LocationReferencesOutput([
            'locationReferences' => $locationReferences,
            'hasNextPage'        => TypeHelper::bool(ArrayHelper::get($pageInfo, 'hasNextPage'), false),
            'endCursor'          => TypeHelper::string(ArrayHelper::get($pageInfo, 'endCursor'), ''),
        ]);
    }
}
