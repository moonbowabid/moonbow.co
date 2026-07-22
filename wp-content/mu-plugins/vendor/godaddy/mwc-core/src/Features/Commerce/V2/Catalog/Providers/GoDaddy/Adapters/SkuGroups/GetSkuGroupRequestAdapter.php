<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanConvertSkuResponseToOutputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\GetSkuGroupOperation;

/**
 * Request adapter for getting a SKU Group using the V2 GraphQL API.
 *
 * @method static static getNewInstance(GetSkuGroupInput $input)
 * @property GetSkuGroupInput $input
 */
class GetSkuGroupRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateSkuGroupFromResponseTrait;
    use CanConvertSkuResponseToOutputTrait;

    public function __construct(GetSkuGroupInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new GetSkuGroupOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id' => $this->input->skuGroupId,
        ];
    }

    /**
     * Converts GraphQL response to SkuGroupRequestOutput.
     *
     * @param ResponseContract $response
     * @return SkuGroupRequestOutput
     */
    protected function convertResponse(ResponseContract $response) : SkuGroupRequestOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $skuGroupData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.skuGroup', []));
        $skusData = GraphQLHelper::extractGraphQLEdges($skuGroupData, 'skus');

        $skus = [];
        foreach ($skusData as $skuData) {
            $skus[] = $this->createSkuFromResponse(TypeHelper::arrayOfStringsAsKeys($skuData));
        }

        return new SkuGroupRequestOutput([
            'skuGroup' => $this->createSkuGroupFromResponse($skuGroupData),
            'skus'     => $skus,
        ]);
    }
}
