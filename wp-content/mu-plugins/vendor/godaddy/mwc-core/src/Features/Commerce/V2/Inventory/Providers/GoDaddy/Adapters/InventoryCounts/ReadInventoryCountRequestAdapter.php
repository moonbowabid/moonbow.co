<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryCountOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts\Traits\CanParseInventoryCountResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries\GetInventoryCountOperation;

/**
 * Adapts a read inventory count request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(ReadInventoryCountInput $input)
 * @property ReadInventoryCountInput $input
 */
class ReadInventoryCountRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanParseInventoryCountResponseTrait;

    /**
     * ReadInventoryCountRequestAdapter constructor.
     *
     * @param ReadInventoryCountInput $input
     */
    public function __construct(ReadInventoryCountInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new GetInventoryCountOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        $vars = [
            'skuId'      => $this->input->skuId,
            'locationId' => $this->input->locationId,
        ];

        if ($this->input->type) {
            $vars['type'] = $this->input->type;
        }

        return $vars;
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response) : ReadInventoryCountOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $inventoryCountsData = ArrayHelper::get($responseBody, 'data.inventoryCount', []);

        if (empty($inventoryCountsData)) {
            return new ReadInventoryCountOutput([]);
        }

        // Convert all inventory counts from the array
        $inventoryCounts = [];
        foreach (TypeHelper::array($inventoryCountsData, []) as $inventoryCountData) {
            $inventoryCounts[] = $this->convertInventoryCountFromGraphQLData(
                TypeHelper::arrayOfStringsAsKeys($inventoryCountData)
            );
        }

        return new ReadInventoryCountOutput([
            'inventoryCounts' => $inventoryCounts,
        ]);
    }
}
