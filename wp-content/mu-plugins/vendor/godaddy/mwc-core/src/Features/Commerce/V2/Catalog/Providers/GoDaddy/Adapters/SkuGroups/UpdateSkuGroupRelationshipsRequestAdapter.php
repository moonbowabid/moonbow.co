<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuGroupRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanBuildRelationshipUpdateVariablesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertRelationshipUpdateResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateSkuGroupRelationshipsOperation;

/**
 * Request adapter for updating SKU Group relationships using the V2 GraphQL API.
 *
 * This adapter dynamically builds GraphQL mutations based on the relationship updates provided,
 * allowing for efficient bulk updates of various SKU Group relationships (media, prices, etc.) in a single request.
 *
 * @method static static getNewInstance(UpdateSkuGroupRelationshipsInput $input)
 * @property UpdateSkuGroupRelationshipsInput $input
 */
class UpdateSkuGroupRelationshipsRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanCreateSkuGroupFromResponseTrait;
    use CanConvertRelationshipUpdateResponseTrait;
    use CanBuildRelationshipUpdateVariablesTrait;

    /**
     * UpdateSkuGroupRelationshipsRequestAdapter constructor.
     *
     * @param UpdateSkuGroupRelationshipsInput $input
     */
    public function __construct(UpdateSkuGroupRelationshipsInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateSkuGroupRelationshipsOperation($this->input->updates))
            ->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        $relationshipsToUpdate = array_filter([
            $this->input->updates->mediaUpdates ?? null,
            $this->input->updates->channelUpdates ?? null,
            $this->input->updates->attributeUpdates ?? null,
            $this->input->updates->attributeValueUpdates ?? null,
            $this->input->updates->listUpdates ?? null,
        ]);

        $variables = [
            'skuGroupId' => $this->input->skuGroupId,
        ];

        return $this->getVariables($relationshipsToUpdate, $variables);
    }

    /**
     * {@inheritDoc}
     */
    protected function convertResponse(ResponseContract $response) : void
    {
        // no-op
    }
}
