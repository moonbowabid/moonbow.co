<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\AddListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\AddListTreeNodesToParentNodeOperation;

/**
 * Request adapter for adding list tree nodes to a parent node.
 *
 * @method static static getNewInstance(AddListTreeNodeInput $input)
 * @property AddListTreeNodeInput $input
 */
class AddListTreeNodesToParentNodeRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(AddListTreeNodeInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new AddListTreeNodesToParentNodeOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        $nodeInput = array_filter([
            'listId'   => $this->input->listId,
            'position' => $this->input->position,
        ], fn ($value) => $value !== null);

        return [
            'id'    => $this->input->parentNodeId,
            'input' => [
                'listTreeNodes' => [$nodeInput],
            ],
        ];
    }

    /**
     * Converts GraphQL response to ListTreeNode.
     *
     * The mutation returns the parent ListTreeNode, so the newly added node is located
     * by matching the input listId against the parent's children.
     *
     * @param ResponseContract $response
     * @return ListTreeNode
     */
    protected function convertResponse(ResponseContract $response) : ListTreeNode
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $edges = TypeHelper::array(ArrayHelper::get($responseBody, 'data.addListTreeNodesToParentListTreeNode.listTreeNodes.edges'), []);

        foreach ($edges as $edge) {
            $node = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($edge, 'node'));
            $nodeListId = TypeHelper::string(ArrayHelper::get($node, 'list.id'), '');

            if ($nodeListId !== '' && $nodeListId === $this->input->listId) {
                return new ListTreeNode([
                    'id'     => TypeHelper::string(ArrayHelper::get($node, 'id'), ''),
                    'listId' => $nodeListId,
                ]);
            }
        }

        return new ListTreeNode(['id' => '', 'listId' => null]);
    }
}
