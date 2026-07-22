<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanBuildSkuInputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanConvertSkuResponseToOutputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateSkuOperation;

/**
 * Request adapter for updating a SKU using the V2 GraphQL API.
 *
 * @method static static getNewInstance(UpdateSkuInput $input)
 * @property UpdateSkuInput $input
 */
class UpdateSkuRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildSkuInputTrait;
    use CanConvertSkuResponseToOutputTrait;
    use CanCreateSkuGroupFromResponseTrait;

    /**
     * UpdateSkuRequestAdapter constructor.
     *
     * @param UpdateSkuInput $input
     */
    public function __construct(UpdateSkuInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateSkuOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->sku->id,
            'input' => $this->buildUpdateSkuInput($this->input->sku),
        ];
    }

    /**
     * Builds the input object for SKU UPDATE mutations.
     * Removes fields not supported by the update schema.
     *
     * @param Sku $sku
     * @return array<string, mixed>
     */
    protected function buildUpdateSkuInput(Sku $sku) : array
    {
        $input = $this->buildSkuInput($sku);

        // Remove fields not supported by update schema
        unset(
            // can only be set during creation
            $input['skuGroupId'],
            $input['createdAt'],

            // can only be set during archival
            $input['archivedAt'],

            // handled via separate inventory mutations
            $input['locations'],

            // handled via separate relationship mutations
            $input['attributeValues'],
            $input['attributes'],
            $input['mediaObjects'],
            $input['prices'],
        );

        return $input;
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

        $skuData = ArrayHelper::get($responseBody, 'data.updateSku', []);

        return $this->convertSkuResponseToOutput($skuData);
    }
}
