<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits\CanConvertSkuResponseToOutputTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanBuildRelationshipUpdateVariablesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertRelationshipUpdateResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\UpdateSkuRelationshipsOperation;

/**
 * Request adapter for updating SKU relationships using the V2 GraphQL API.
 *
 * This adapter dynamically builds GraphQL mutations based on the relationship updates provided,
 * allowing for efficient bulk updates of various SKU relationships (media, prices, etc.) in a single request.
 *
 * @method static static getNewInstance(UpdateSkuRelationshipsInput $input)
 * @property UpdateSkuRelationshipsInput $input
 */
class UpdateSkuRelationshipsRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanConvertSkuResponseToOutputTrait;
    use CanConvertRelationshipUpdateResponseTrait;
    use CanBuildRelationshipUpdateVariablesTrait;

    /**
     * UpdateSkuRelationshipsRequestAdapter constructor.
     *
     * @param UpdateSkuRelationshipsInput $input
     */
    public function __construct(UpdateSkuRelationshipsInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new UpdateSkuRelationshipsOperation($this->input->updates))
            ->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        $relationshipsToUpdate = array_filter([
            $this->input->updates->mediaUpdates ?? null,
            $this->input->updates->priceUpdates ?? null,
            $this->input->updates->channelUpdates ?? null,
            $this->input->updates->attributeUpdates ?? null,
            $this->input->updates->attributeValueUpdates ?? null,
        ]);

        $variables = [
            'skuId' => $this->input->skuId,
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
