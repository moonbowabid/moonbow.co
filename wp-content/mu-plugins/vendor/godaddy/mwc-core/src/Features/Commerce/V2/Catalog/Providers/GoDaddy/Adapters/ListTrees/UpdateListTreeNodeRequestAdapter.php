<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateListTreeNodeOperation;

/**
 * Request adapter for updating a list tree node.
 *
 * @method static static getNewInstance(UpdateListTreeNodeInput $input)
 * @property UpdateListTreeNodeInput $input
 */
class UpdateListTreeNodeRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(UpdateListTreeNodeInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateListTreeNodeOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->listTreeNodeId,
            'input' => $this->buildUpdateListTreeNodeInput(),
        ];
    }

    /**
     * Builds the input object for the UpdateListTreeNode mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildUpdateListTreeNodeInput() : array
    {
        return array_filter([
            'parentListTreeNodeId' => $this->input->parentNodeId,
            'position'             => $this->input->position,
        ], fn ($value) => $value !== null);
    }

    /**
     * Converts GraphQL response to ListTreeNode.
     *
     * @param ResponseContract $response
     * @return ListTreeNode
     */
    protected function convertResponse(ResponseContract $response) : ListTreeNode
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $nodeData = ArrayHelper::get($responseBody, 'data.updateListTreeNode', []);
        $parentNodeId = TypeHelper::string(ArrayHelper::get($nodeData, 'parentListTreeNode.id'), '');

        return new ListTreeNode([
            'id'                   => TypeHelper::string(ArrayHelper::get($nodeData, 'id'), ''),
            'parentListTreeNodeId' => $parentNodeId ?: null,
        ]);
    }
}
