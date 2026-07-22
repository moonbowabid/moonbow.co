<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTree;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateListTreeOperation;

/**
 * Request adapter for updating a list tree.
 *
 * @method static static getNewInstance(UpdateListTreeInput $input)
 * @property UpdateListTreeInput $input
 */
class UpdateListTreeRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(UpdateListTreeInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateListTreeOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->listTreeId,
            'input' => $this->buildUpdateListTreeInput(),
        ];
    }

    /**
     * Builds the input object for the UpdateListTree mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildUpdateListTreeInput() : array
    {
        return array_filter([
            'label'       => $this->input->label,
            'description' => $this->input->description,
        ], fn ($value) => $value !== null);
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
        $treeData = ArrayHelper::get($responseBody, 'data.updateListTree', []);

        return new ListTree([
            'id' => TypeHelper::string(ArrayHelper::get($treeData, 'id'), ''),
        ]);
    }
}
