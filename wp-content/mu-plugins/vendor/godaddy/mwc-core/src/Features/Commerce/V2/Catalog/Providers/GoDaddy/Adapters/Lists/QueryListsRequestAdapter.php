<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\ListCategoriesResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\QueryListsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\QueryListsOperation;

/**
 * Adapter for querying lists in the GoDaddy Commerce Catalog v2 API.
 * @NOTE Right now this only queries `List` objects directly. In the future we may want to add additional filtering
 * to ensure that we only return `List` objects that also appear in the dedicated category `ListTree`.
 *
 * @method static static getNewInstance(QueryListsInput $input)
 * @property QueryListsInput $input
 */
class QueryListsRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(QueryListsInput $input)
    {
        parent::__construct($input);
    }

    /**
     * Converts GraphQL response to ListCategoriesResponseContract.
     *
     * @param ResponseContract $response
     * @return ListCategoriesResponseContract
     */
    protected function convertResponse(ResponseContract $response) : ListCategoriesResponseContract
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $listsData = TypeHelper::array(
            ArrayHelper::get($responseBody, 'data.lists.edges', []),
            []
        );

        $categories = [];

        foreach ($listsData as $edge) {
            $listNode = TypeHelper::array(ArrayHelper::get($edge, 'node', []), []);

            if (! empty($listNode)) {
                $categories[] = $this->convertListNodeToCategory($listNode);
            }
        }

        return new ListCategoriesResponse($categories);
    }

    /**
     * Converts a list node from GraphQL response to a Category object.
     *
     * @param array<mixed> $listNode
     * @return Category
     */
    protected function convertListNodeToCategory(array $listNode) : Category
    {
        return new Category([
            'altId'       => TypeHelper::string(ArrayHelper::get($listNode, 'name'), ''),
            'categoryId'  => TypeHelper::string(ArrayHelper::get($listNode, 'id'), ''),
            'name'        => TypeHelper::string(ArrayHelper::get($listNode, 'label'), ''),
            'description' => TypeHelper::stringOrNull(ArrayHelper::get($listNode, 'description')),
            'createdAt'   => TypeHelper::stringOrNull(ArrayHelper::get($listNode, 'createdAt')),
            'updatedAt'   => TypeHelper::stringOrNull(ArrayHelper::get($listNode, 'updatedAt')),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new QueryListsOperation())->setVariables($this->getQueryVariables());
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryVariables() : array
    {
        $variables = [];

        // Only include name filter if name is provided
        if (! empty($this->input->name)) {
            $variables['name'] = [
                'eq' => $this->input->name,
            ];
        }

        return $variables;
    }
}
