<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanConvertSkuResponseToOutputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\GetSkuOperation;

/**
 * Request adapter for getting a SKU using the V2 GraphQL API.
 *
 * @method static static getNewInstance(GetSkuInput $input)
 * @property GetSkuInput $input
 */
class GetSkuRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanConvertSkuResponseToOutputTrait;
    use CanCreateSkuGroupFromResponseTrait;

    public function __construct(GetSkuInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new GetSkuOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id' => $this->input->skuId,
        ];
    }

    /**
     * Converts GraphQL response to SkuRequestOutput.
     *
     * @param ResponseContract $response
     * @return SkuRequestOutput
     * @throws MissingProductRemoteIdException|CommerceExceptionContract
     */
    protected function convertResponse(ResponseContract $response) : SkuRequestOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $skuData = ArrayHelper::get($responseBody, 'data.sku', []);

        return $this->convertSkuResponseToOutput($skuData);
    }
}
