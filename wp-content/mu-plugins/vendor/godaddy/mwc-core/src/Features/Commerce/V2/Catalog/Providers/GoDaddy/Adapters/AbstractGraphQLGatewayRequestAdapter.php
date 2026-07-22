<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Adapters\Traits\CanHandleGraphQLErrorsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Http\GraphQL\Requests\Request;

abstract class AbstractGraphQLGatewayRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanHandleGraphQLErrorsTrait;

    protected StoreIdRequestInput $input;

    public function __construct(StoreIdRequestInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts from source input to GraphQL request.
     *
     * @return Request
     */
    public function convertFromSource() : Request
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
    abstract protected function getGraphQLOperation() : GraphQLOperationContract;

    /**
     * Gets query variables for the GraphQL operation.
     *
     * @return array<string, mixed>
     */
    abstract protected function getQueryVariables() : array;
}
