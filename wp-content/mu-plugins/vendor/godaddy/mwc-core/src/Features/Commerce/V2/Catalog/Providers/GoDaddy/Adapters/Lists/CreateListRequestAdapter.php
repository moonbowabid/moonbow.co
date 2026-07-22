<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\CreateListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateListObjectFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\CreateListOperation;

/**
 * Request adapter for creating a category using the V2 GraphQL API.
 *
 * @method static static getNewInstance(CreateListInput $input)
 * @property CreateListInput $input
 */
class CreateListRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateListObjectFromResponseTrait;

    /**
     * CreateListRequestAdapter constructor.
     *
     * @param CreateListInput $input
     */
    public function __construct(CreateListInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new CreateListOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'input' => $this->buildCreateListInput(),
        ];
    }

    /**
     * Builds the input object for the CreateList mutation.
     *
     * @return array<string, mixed>
     */
    protected function buildCreateListInput() : array
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
     * @throws MissingCategoryRemoteIdException|CommerceExceptionContract
     */
    protected function convertResponse(ResponseContract $response) : ListObject
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());

        $listData = ArrayHelper::get($responseBody, 'data.createList', []);

        $listId = TypeHelper::string(ArrayHelper::get($listData, 'id'), '');

        if (empty($listId)) {
            throw new MissingCategoryRemoteIdException('The category ID was not returned from the response.');
        }

        return $this->createListObjectFromResponse(TypeHelper::arrayOfStringsAsKeys($listData));
    }
}
