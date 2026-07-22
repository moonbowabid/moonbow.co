<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTree;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\AddListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateListTreeFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\AddListTreeNodesToListTreeOperation;

/**
 * Request adapter for adding list tree nodes to a list tree.
 *
 * @method static static getNewInstance(AddListTreeNodeInput $input)
 * @property AddListTreeNodeInput $input
 */
class AddListTreeNodesToListTreeRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateListTreeFromResponseTrait;

    public function __construct(AddListTreeNodeInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new AddListTreeNodesToListTreeOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        $nodeInput = array_filter([
            'listId'   => $this->input->listId,
            'position' => $this->input->position,
        ], fn ($value) => $value !== null);

        return [
            'id'    => $this->input->listTreeId,
            'input' => [
                'listTreeNodes' => [$nodeInput],
            ],
        ];
    }

    /**
     * Converts GraphQL response to ListTree.
     *
     * @param ResponseContract $response
     * @return ListTree
     */
    protected function convertResponse(ResponseContract $response) : ListTree
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $treeData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.addListTreeNodesToListTree', []));

        return $this->createListTreeFromResponse($treeData);
    }
}
