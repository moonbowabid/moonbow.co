<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\ArchiveSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\ArchiveSkuGroupOperation;

/**
 * Request adapter for archiving a SKU Group using the V2 GraphQL API.
 *
 * @method static static getNewInstance(ArchiveSkuGroupInput $input)
 * @property ArchiveSkuGroupInput $input
 */
class ArchiveSkuGroupRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateSkuGroupFromResponseTrait;

    public function __construct(ArchiveSkuGroupInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ArchiveSkuGroupOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'             => $this->input->skuGroupId,
            'cascadeArchive' => ['skus' => $this->input->cascadeSkus],
        ];
    }

    /**
     * Converts GraphQL response to SkuGroupRequestOutput.
     *
     * @param ResponseContract $response
     * @return SkuGroupRequestOutput
     * @throws MissingProductRemoteIdException
     */
    protected function convertResponse(ResponseContract $response) : SkuGroupRequestOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());

        /** @var array<string, mixed> $skuGroupData */
        $skuGroupData = ArrayHelper::get($responseBody, 'data.archiveSkuGroup', []);

        $skuGroupId = TypeHelper::string(ArrayHelper::get($skuGroupData, 'id'), '');

        if (empty($skuGroupId)) {
            throw new MissingProductRemoteIdException('The SKU Group ID was not returned from the response.');
        }

        return new SkuGroupRequestOutput([
            'skuGroup' => $this->createSkuGroupFromResponse($skuGroupData),
        ]);
    }
}
