<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\Traits\CanBuildSkuGroupInputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCleanAttributeDataTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanRemoveNullPropertiesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\CreateSkuGroupOperation;

/**
 * Request adapter for creating a SKU Group using the V2 GraphQL API.
 *
 * @method static static getNewInstance(CreateSkuGroupInput $input)
 * @property CreateSkuGroupInput $input
 */
class CreateSkuGroupRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildSkuGroupInputTrait;
    use CanCleanAttributeDataTrait;
    use CanCreateSkuGroupFromResponseTrait;
    use CanRemoveNullPropertiesTrait;

    /**
     * CreateSkuGroupRequestAdapter constructor.
     *
     * @param CreateSkuGroupInput $input
     */
    public function __construct(CreateSkuGroupInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new CreateSkuGroupOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'input' => $this->buildCreateSkuGroupInput($this->input->skuGroup),
        ];
    }

    /**
     * Converts GraphQL response to SkuGroupRequestOutput.
     *
     * @param ResponseContract $response
     * @return SkuGroupRequestOutput
     * @throws MissingProductRemoteIdException|CommerceExceptionContract
     */
    protected function convertResponse(ResponseContract $response) : SkuGroupRequestOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());

        /** @var array<string, mixed> $skuGroupData */
        $skuGroupData = ArrayHelper::get($responseBody, 'data.createSkuGroup', []);

        $skuGroupId = TypeHelper::string(ArrayHelper::get($skuGroupData, 'id'), '');

        if (empty($skuGroupId)) {
            throw new MissingProductRemoteIdException('The SKU Group ID was not returned from the response.');
        }

        // Create SkuGroup from response data using trait method
        $skuGroup = $this->createSkuGroupFromResponse($skuGroupData);

        return new SkuGroupRequestOutput([
            'skuGroup' => $skuGroup,
        ]);
    }

    /**
     * @param SkuGroup $skuGroup
     * @return array<int|string, mixed>
     */
    protected function buildCreateSkuGroupInput(SkuGroup $skuGroup) : array
    {
        $input = $this->buildSkuGroupInput($skuGroup);

        // Remove null IDs from all nested objects (for creation they don't exist yet)
        $input = $this->removeNullProperty($input, 'id');

        // Additional cleanup for attributes - ensure no id fields remain
        if (ArrayHelper::accessible($input['attributes'] ?? null)) {
            $input['attributes'] = $this->cleanAttributesForCreation($input['attributes']);
        }

        return ArrayHelper::except($input, 'archivedAt');
    }
}
