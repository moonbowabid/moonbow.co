<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Products;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\QueryProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\QueryProductsOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanConvertSkuResponseToOutputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\QueryProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Http\GraphQL\Requests\Request;

/**
 * Request adapter for querying a paginated page of products from the v2 Catalog GraphQL API.
 *
 * @method static static getNewInstance(QueryProductsInput $input)
 */
class QueryProductsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanCreateSkuGroupFromResponseTrait;
    use CanConvertSkuResponseToOutputTrait;

    protected QueryProductsInput $input;

    public function __construct(QueryProductsInput $input)
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
        return (new QueryProductsOperation())->setVariables($this->getQueryVariables());
    }

    /**
     * Gets query variables for the GraphQL operation.
     *
     * @return array<string, mixed>
     */
    protected function getQueryVariables() : array
    {
        $variables = [
            'first' => $this->input->perPage,
        ];

        if (! is_null($this->input->cursor)) {
            $variables['after'] = $this->input->cursor;
        }

        if (! is_null($this->input->status)) {
            $variables['status'] = ['eq' => $this->input->status];
        }

        return $variables;
    }

    /**
     * Converts the GraphQL response into a paginated product page.
     *
     * @param ResponseContract $response
     * @return QueryProductsOutput
     */
    protected function convertResponse(ResponseContract $response) : QueryProductsOutput
    {
        $body = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $edges = TypeHelper::array(ArrayHelper::get($body, 'data.skuGroups.edges', []), []);
        $pageInfo = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($body, 'data.skuGroups.pageInfo', []));

        $skuGroups = [];
        foreach ($edges as $edge) {
            $node = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($edge, 'node', []));

            if (empty($node)) {
                continue;
            }

            $skuGroup = $this->createSkuGroupFromResponse($node);

            foreach (GraphQLHelper::extractGraphQLEdges($node, 'skus') as $skuNode) {
                $skuGroup->skus[] = $this->createSkuFromResponse(TypeHelper::arrayOfStringsAsKeys($skuNode));
            }

            $skuGroups[] = $skuGroup;
        }

        return new QueryProductsOutput([
            'skuGroups'   => $skuGroups,
            'hasNextPage' => TypeHelper::bool(ArrayHelper::get($pageInfo, 'hasNextPage'), false),
            'endCursor'   => TypeHelper::stringOrNull(ArrayHelper::get($pageInfo, 'endCursor')),
        ]);
    }
}
