<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\UpdateListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateListObjectFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateListOperation;

/**
 * Request adapter for updating a list using the V2 GraphQL API.
 *
 * @method static static getNewInstance(UpdateListInput $input)
 * @property UpdateListInput $input
 */
class UpdateListRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateListObjectFromResponseTrait;

    public function __construct(UpdateListInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateListOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->list->id,
            'input' => $this->buildUpdateListInput(),
        ];
    }

    /**
     * Builds the input object for the UpdateList mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildUpdateListInput() : array
    {
        return [
            'name'        => $this->input->list->name,
            'label'       => $this->input->list->label,
            'description' => $this->input->list->description,
            'status'      => $this->input->list->status,
        ];
    }

    /**
     * Converts GraphQL response to ListObject.
     *
     * @param ResponseContract $response
     * @return ListObject
     */
    protected function convertResponse(ResponseContract $response) : ListObject
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());

        $listData = ArrayHelper::get($responseBody, 'data.updateList', []);

        return $this->createListObjectFromResponse(TypeHelper::arrayOfStringsAsKeys($listData));
    }
}
