<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTree;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\CreateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateListTreeFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\CreateListTreeOperation;

/**
 * Request adapter for creating a list tree.
 *
 * @method static static getNewInstance(CreateListTreeInput $input)
 * @property CreateListTreeInput $input
 */
class CreateListTreeRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateListTreeFromResponseTrait;

    public function __construct(CreateListTreeInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new CreateListTreeOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'input' => $this->buildCreateListTreeInput(),
        ];
    }

    /**
     * Builds the input object for the CreateListTree mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildCreateListTreeInput() : array
    {
        return array_filter([
            'label'         => $this->input->label,
            'name'          => $this->input->name,
            'description'   => $this->input->description,
            'status'        => $this->input->status,
            'listTreeNodes' => $this->input->listTreeNodes,
        ], fn ($value) => $value !== null);
    }

    /**
     * Converts GraphQL response to ListTree.
     *
     * @param ResponseContract $response
     * @return ListTree
     * @throws MissingCategoryRemoteIdException|CommerceExceptionContract
     */
    protected function convertResponse(ResponseContract $response) : ListTree
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $treeData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.createListTree', []));
        $treeId = TypeHelper::string(ArrayHelper::get($treeData, 'id'), '');

        if (empty($treeId)) {
            throw new MissingCategoryRemoteIdException('The list tree ID was not returned from the response.');
        }

        return $this->createListTreeFromResponse($treeData);
    }
}
