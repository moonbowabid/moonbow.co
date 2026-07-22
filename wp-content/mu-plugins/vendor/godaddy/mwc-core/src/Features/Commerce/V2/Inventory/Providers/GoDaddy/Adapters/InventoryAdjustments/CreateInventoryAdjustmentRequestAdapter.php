<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CreateInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\CreateInventoryAdjustmentOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments\Traits\CanParseInventoryAdjustmentResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations\CreateInventoryAdjustmentOperation;

/**
 * Adapts a create inventory adjustment request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(CreateInventoryAdjustmentInput $input)
 * @property CreateInventoryAdjustmentInput $input
 */
class CreateInventoryAdjustmentRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanParseInventoryAdjustmentResponseTrait;

    /**
     * CreateInventoryAdjustmentRequestAdapter constructor.
     *
     * @param CreateInventoryAdjustmentInput $input
     */
    public function __construct(CreateInventoryAdjustmentInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new CreateInventoryAdjustmentOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        $vars = [
            'input' => [
                'delta'      => $this->input->delta,
                'locationId' => $this->input->locationId,
                'skuId'      => $this->input->skuId,
                'type'       => $this->input->type,
            ],
        ];

        if ($this->input->occurredAt) {
            $vars['input']['occurredAt'] = $this->input->occurredAt->format('c');
        }

        return $vars;
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response) : CreateInventoryAdjustmentOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $adjustmentData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($responseBody, 'data.createInventoryAdjustment', []));

        return new CreateInventoryAdjustmentOutput([
            'inventoryAdjustment' => $this->createInventoryAdjustmentFromResponse($adjustmentData),
        ]);
    }
}
