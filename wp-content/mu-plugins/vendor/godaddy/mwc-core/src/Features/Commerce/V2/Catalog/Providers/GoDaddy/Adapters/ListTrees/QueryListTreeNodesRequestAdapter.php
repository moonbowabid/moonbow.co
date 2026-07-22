<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\QueryListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\QueryListTreeNodesOperation;

/**
 * Request adapter for querying ListTreeNodes by list ID.
 *
 * @method static static getNewInstance(QueryListTreeNodesInput $input)
 * @property QueryListTreeNodesInput $input
 */
class QueryListTreeNodesRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(QueryListTreeNodesInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new QueryListTreeNodesOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'listId' => [
                'eq' => $this->input->listId,
            ],
        ];
    }

    /**
     * Converts the GraphQL response to a ListTreeNode or null if not found.
     *
     * @param ResponseContract $response
     * @return ?ListTreeNode
     */
    protected function convertResponse(ResponseContract $response) : ?ListTreeNode
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $edges = TypeHelper::array(ArrayHelper::get($responseBody, 'data.listTreeNodes.edges'), []);

        $firstEdge = TypeHelper::array(ArrayHelper::get($edges, '0'), []);
        $nodeData = TypeHelper::array(ArrayHelper::get($firstEdge, 'node'), []);

        if (empty($nodeData)) {
            return null;
        }

        return new ListTreeNode([
            'id'                 => TypeHelper::stringOrNull(ArrayHelper::get($nodeData, 'id')),
            'list'               => $this->convertListObject(TypeHelper::array(ArrayHelper::get($nodeData, 'list'), [])),
            'parentListTreeNode' => $this->convertParentNode(
                TypeHelper::array(ArrayHelper::get($nodeData, 'parentListTreeNode'), [])
            ),
        ]);
    }

    /**
     * Recursively converts a parent node from the response into a ListTreeNode.
     *
     * @param array<mixed> $parentData
     * @return ?ListTreeNode
     */
    protected function convertParentNode(array $parentData) : ?ListTreeNode
    {
        if (empty($parentData)) {
            return null;
        }

        return new ListTreeNode([
            'id'                 => TypeHelper::stringOrNull(ArrayHelper::get($parentData, 'id')),
            'list'               => $this->convertListObject(TypeHelper::array(ArrayHelper::get($parentData, 'list'), [])),
            'parentListTreeNode' => $this->convertParentNode(
                TypeHelper::array(ArrayHelper::get($parentData, 'parentListTreeNode'), [])
            ),
        ]);
    }

    /**
     * Converts list data from the response into a ListObject.
     *
     * @param array<mixed> $listData
     * @return ?ListObject
     */
    protected function convertListObject(array $listData) : ?ListObject
    {
        if (empty($listData)) {
            return null;
        }

        return new ListObject([
            'id'              => TypeHelper::stringOrNull(ArrayHelper::get($listData, 'id')),
            'name'            => TypeHelper::string(ArrayHelper::get($listData, 'name'), ''),
            'label'           => TypeHelper::string(ArrayHelper::get($listData, 'label'), ''),
            'description'     => TypeHelper::stringOrNull(ArrayHelper::get($listData, 'description')),
            'htmlDescription' => TypeHelper::stringOrNull(ArrayHelper::get($listData, 'htmlDescription')),
            'status'          => TypeHelper::stringOrNull(ArrayHelper::get($listData, 'status')),
            'createdAt'       => TypeHelper::stringOrNull(ArrayHelper::get($listData, 'createdAt')),
            'updatedAt'       => TypeHelper::stringOrNull(ArrayHelper::get($listData, 'updatedAt')),
        ]);
    }
}
