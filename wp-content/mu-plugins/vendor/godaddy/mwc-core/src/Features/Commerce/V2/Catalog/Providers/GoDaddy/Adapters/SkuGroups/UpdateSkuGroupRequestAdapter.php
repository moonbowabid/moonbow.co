<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\Traits\CanBuildSkuGroupInputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateSkuGroupOperation;

/**
 * Request adapter for updating a SKU Group using the V2 GraphQL API.
 *
 * @method static static getNewInstance(UpdateSkuGroupInput $input)
 * @property UpdateSkuGroupInput $input
 */
class UpdateSkuGroupRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanBuildSkuGroupInputTrait;
    use CanCreateSkuGroupFromResponseTrait;

    /**
     * UpdateSkuGroupRequestAdapter constructor.
     *
     * @param UpdateSkuGroupInput $input
     */
    public function __construct(UpdateSkuGroupInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateSkuGroupOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id'    => $this->input->skuGroup->id,
            'input' => $this->buildUpdateSkuGroupInput($this->input->skuGroup),
        ];
    }

    /**
     * Builds the input object for SKU Group UPDATE mutations.
     * Removes fields not supported by the update schema.
     *
     * @param SkuGroup $skuGroup
     * @return array<string, mixed>
     */
    protected function buildUpdateSkuGroupInput(SkuGroup $skuGroup) : array
    {
        $input = $this->buildSkuGroupInput($skuGroup);

        // Remove fields not supported by update schema
        unset(
            // can only be set during creation
            $input['createdAt'],

            // can only be set during archival
            $input['archivedAt'],

            // unsupported for UPDATE operations
            $input['type'],
            $input['attributes'],
            $input['channels'],
            $input['mediaObjects'],
            $input['options'],
            $input['references'],
        );

        return $input;
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
        $skuGroupData = ArrayHelper::get($responseBody, 'data.updateSkuGroup', []);

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
}
