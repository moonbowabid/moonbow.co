<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\ArchiveListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateListObjectFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\ArchiveListOperation;

/**
 * Request adapter for archiving a list using the V2 GraphQL API.
 *
 * @method static static getNewInstance(ArchiveListInput $input)
 * @property ArchiveListInput $input
 */
class ArchiveListRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateListObjectFromResponseTrait;

    public function __construct(ArchiveListInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ArchiveListOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id' => $this->input->listId,
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

        $listData = ArrayHelper::get($responseBody, 'data.archiveList', []);

        $listId = TypeHelper::string(ArrayHelper::get($listData, 'id'), '');

        if (empty($listId)) {
            throw new MissingCategoryRemoteIdException('The archived category ID was not returned from the response.');
        }

        return $this->createListObjectFromResponse(TypeHelper::arrayOfStringsAsKeys($listData));
    }
}
