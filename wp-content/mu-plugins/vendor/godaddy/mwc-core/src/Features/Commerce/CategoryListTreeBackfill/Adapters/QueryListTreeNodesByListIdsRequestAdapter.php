<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\ListTreeNodeMapping;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\QueryListTreeNodesByListIdsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Http\GraphQL\Requests\Request;

/**
 * Request adapter for querying list tree nodes by list IDs.
 */
class QueryListTreeNodesByListIdsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected QueryListTreeNodesByListIdsInput $input;

    public function __construct(QueryListTreeNodesByListIdsInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts from source input to GraphQL request.
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth($this->getGraphQLOperation())
            ->setStoreId($this->input->storeId)
            ->setMethod('post');
    }

    /**
     * Gets the GraphQL operation.
     */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new QueryListTreeNodesByListIdsOperation())->setVariables($this->getQueryVariables());
    }

    /**
     * Gets query variables for the GraphQL operation.
     *
     * @return array<string, mixed>
     */
    protected function getQueryVariables() : array
    {
        return [
            'first'   => $this->input->perPage,
            'after'   => $this->input->cursor,
            'listIds' => $this->input->listIds,
        ];
    }

    /**
     * Converts GraphQL response to output object.
     */
    protected function convertResponse(ResponseContract $response) : QueryListTreeNodesByListIdsOutput
    {
        $responseBody = $response->getBody();
        $listTreeNodesData = ArrayHelper::get($responseBody, 'data.listTreeNodes', []);

        $edges = TypeHelper::array(ArrayHelper::get($listTreeNodesData, 'edges'), []);
        $mappings = [];

        foreach ($edges as $edge) {
            $node = TypeHelper::array(ArrayHelper::get($edge, 'node'), []);

            $listTreeNodeId = TypeHelper::string(ArrayHelper::get($node, 'id'), '');
            $listId = TypeHelper::string(ArrayHelper::get($node, 'list.id'), '');
            $listTreeId = TypeHelper::string(ArrayHelper::get($node, 'listTree.id'), '');

            if (! $listTreeNodeId || ! $listId || ! $listTreeId) {
                continue;
            }

            $mappings[] = new ListTreeNodeMapping([
                'listTreeNodeId'       => $listTreeNodeId,
                'listId'               => $listId,
                'listTreeId'           => $listTreeId,
                'parentListTreeNodeId' => TypeHelper::string(ArrayHelper::get($node, 'parentListTreeNode.id'), '') ?: null,
            ]);
        }

        $pageInfo = ArrayHelper::get($listTreeNodesData, 'pageInfo', []);

        return new QueryListTreeNodesByListIdsOutput([
            'mappings'    => $mappings,
            'hasNextPage' => TypeHelper::bool(ArrayHelper::get($pageInfo, 'hasNextPage'), false),
            'endCursor'   => TypeHelper::string(ArrayHelper::get($pageInfo, 'endCursor'), '') ?: null,
        ]);
    }
}
