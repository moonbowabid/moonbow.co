<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ListInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ListInventoryCountOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts\Traits\CanParseInventoryCountResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Queries\ListInventoryCountsOperation;

/**
 * Adapts a list inventory counts request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(ListInventoryCountInput $input)
 * @property ListInventoryCountInput $input
 */
class ListInventoryCountRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    use CanParseInventoryCountResponseTrait;

    /**
     * ListInventoryCountRequestAdapter constructor.
     *
     * @param ListInventoryCountInput $input
     */
    public function __construct(ListInventoryCountInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ListInventoryCountsOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'skuIds' => $this->input->skuIds,
        ];
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response) : ListInventoryCountOutput
    {
        $groupedInventoryCounts = $this->extractAndGroupInventoryCountsFromSkuResponse($response);

        return new ListInventoryCountOutput([
            'groupedInventoryCounts' => $groupedInventoryCounts,
        ]);
    }

    /**
     * Extracts and groups inventory counts from the SKU-based GraphQL response.
     *
     * @param ResponseContract $response
     * @return array<string, InventoryCount[]>
     */
    protected function extractAndGroupInventoryCountsFromSkuResponse(ResponseContract $response) : array
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $skuNodes = GraphQLHelper::extractGraphQLEdges($responseBody, 'data.skus');

        $groupedInventoryCounts = [];

        foreach ($skuNodes as $skuNode) {
            $skuNode = TypeHelper::arrayOfStringsAsKeys($skuNode);
            $skuId = TypeHelper::string(ArrayHelper::get($skuNode, 'id', ''), '');

            $skuInventoryCounts = $this->extractInventoryCountsForSku($skuNode);

            // Always include the SKU in the result, even if it has no (filtered) inventory counts.
            // This ensures that every requested SKU is represented in the output, even if its inventory counts are filtered out.
            // Empty arrays are intentionally preserved to indicate SKUs with no available inventory counts.
            $groupedInventoryCounts[$skuId] = $skuInventoryCounts;
        }

        return $groupedInventoryCounts;
    }

    /**
     * Extracts inventory counts for a single SKU from GraphQL data.
     *
     * @param array<string, mixed> $skuNode
     * @return InventoryCount[]
     */
    protected function extractInventoryCountsForSku(array $skuNode) : array
    {
        $inventoryCountNodes = GraphQLHelper::extractGraphQLEdges($skuNode, 'inventoryCounts');
        $skuInventoryCounts = [];

        foreach ($inventoryCountNodes as $countNode) {
            $countNode = TypeHelper::arrayOfStringsAsKeys($countNode);

            $inventoryCount = $this->buildInventoryCountFromGraphQLNodes($countNode, $skuNode);

            // Filter by location and type during extraction
            if ($this->shouldIncludeInventoryCount($inventoryCount)) {
                $skuInventoryCounts[] = $inventoryCount;
            }
        }

        return $skuInventoryCounts;
    }

    /**
     * Builds an InventoryCount object from separate GraphQL count and SKU nodes.
     *
     * @param array<string, mixed> $inventoryCountNode
     * @param array<string, mixed> $skuNode
     * @return InventoryCount
     */
    protected function buildInventoryCountFromGraphQLNodes(array $inventoryCountNode, array $skuNode) : InventoryCount
    {
        return $this->makeInventoryCountObjectFromNode($inventoryCountNode, $this->makeSkuObjectFromNode($skuNode));
    }

    /**
     * Determines if an inventory count should be included based on location and type filters.
     *
     * @param InventoryCount $count
     * @return bool
     */
    protected function shouldIncludeInventoryCount(InventoryCount $count) : bool
    {
        // Filter by location ID if specified
        if ($this->input->locationId && $count->locationId !== $this->input->locationId) {
            return false;
        }

        // Filter by type if specified
        if ($this->input->type && $count->type !== $this->input->type) {
            return false;
        }

        return true;
    }
}
