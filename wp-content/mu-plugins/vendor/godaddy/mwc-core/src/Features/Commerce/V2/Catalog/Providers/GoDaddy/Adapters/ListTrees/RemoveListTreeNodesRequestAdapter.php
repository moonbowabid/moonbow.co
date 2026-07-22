<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\RemoveListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\RemoveListTreeNodesOperation;

/**
 * Request adapter for removing list tree nodes from a list tree.
 *
 * @method static static getNewInstance(RemoveListTreeNodesInput $input)
 * @property RemoveListTreeNodesInput $input
 */
class RemoveListTreeNodesRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(RemoveListTreeNodesInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new RemoveListTreeNodesOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->listTreeId,
            'input' => [
                'listTreeNodeIds' => $this->input->listTreeNodeIds,
            ],
        ];
    }

    /**
     * @param ResponseContract $response
     * @return void
     */
    protected function convertResponse(ResponseContract $response) : void
    {
        // no-op
    }
}
