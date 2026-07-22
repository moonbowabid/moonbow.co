<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryAdjustmentOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments\Traits\CanParseInventoryAdjustmentResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries\ReadInventoryAdjustmentOperation;

/**
 * Adapts a read inventory adjustment request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(ReadInventoryAdjustmentInput $input)
 * @property ReadInventoryAdjustmentInput $input
 */
class ReadInventoryAdjustmentRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanParseInventoryAdjustmentResponseTrait;

    /**
     * ReadInventoryAdjustmentRequestAdapter constructor.
     *
     * @param ReadInventoryAdjustmentInput $input
     */
    public function __construct(ReadInventoryAdjustmentInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ReadInventoryAdjustmentOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id' => $this->input->id,
        ];
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response) : ReadInventoryAdjustmentOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $inventoryAdjustmentData = ArrayHelper::get($responseBody, 'data.inventoryAdjustment', []);

        $inventoryAdjustment = $this->createInventoryAdjustmentFromResponse(
            TypeHelper::arrayOfStringsAsKeys($inventoryAdjustmentData)
        );

        return new ReadInventoryAdjustmentOutput([
            'inventoryAdjustment' => $inventoryAdjustment,
        ]);
    }
}
