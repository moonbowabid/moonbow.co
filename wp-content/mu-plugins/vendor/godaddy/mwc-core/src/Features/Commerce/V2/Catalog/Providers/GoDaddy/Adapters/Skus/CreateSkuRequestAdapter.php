<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanBuildSkuInputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanConvertSkuResponseToOutputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanRemoveNullPropertiesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\CreateSkuOperation;

/**
 * Request adapter for creating a SKU using the V2 GraphQL API.
 *
 * @method static static getNewInstance(CreateSkuInput $input)
 * @property CreateSkuInput $input
 */
class CreateSkuRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildSkuInputTrait;
    use CanConvertSkuResponseToOutputTrait;
    use CanCreateSkuGroupFromResponseTrait;
    use CanRemoveNullPropertiesTrait;

    /**
     * CreateSkuRequestAdapter constructor.
     *
     * @param CreateSkuInput $input
     */
    public function __construct(CreateSkuInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new CreateSkuOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'input' => $this->buildCreateSkuInput($this->input->sku),
        ];
    }

    /**
     * @param Sku $sku
     * @return array<int|string, mixed>
     */
    protected function buildCreateSkuInput(Sku $sku) : array
    {
        $input = $this->buildSkuInput($sku);

        // Remove null IDs from all nested objects (for creation they don't exist yet)
        $input = $this->removeNullProperty($input, 'id');

        unset(
            $input['archivedAt'],
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

        $skuData = ArrayHelper::get($responseBody, 'data.createSku', []);

        return $this->convertSkuResponseToOutput($skuData);
    }
}
