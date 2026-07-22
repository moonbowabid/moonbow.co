<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Metafield;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\Sku;

/**
 * Trait for parsing inventory counts from a payload.
 */
trait CanParseInventoryCountResponseTrait
{
    /**
     * Converts GraphQL inventory count data to an InventoryCount object.
     *
     * @param array<string, mixed> $inventoryCountData
     * @return InventoryCount
     */
    protected function convertInventoryCountFromGraphQLData(array $inventoryCountData) : InventoryCount
    {
        $skuData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($inventoryCountData, 'sku', []));

        return $this->makeInventoryCountObjectFromNode($inventoryCountData, $this->makeSkuObjectFromNode($skuData));
    }

    /**
     * Creates a Sku object from GraphQL node data.
     *
     * @param array<string, mixed> $skuData
     * @return Sku
     */
    protected function makeSkuObjectFromNode(array $skuData) : Sku
    {
        $backorderLimit = ArrayHelper::get($skuData, 'backorderLimit');

        return new Sku([
            'id'             => TypeHelper::string(ArrayHelper::get($skuData, 'id', ''), ''),
            'backorderLimit' => is_numeric($backorderLimit) ? TypeHelper::int($backorderLimit, 0) : null,
            'metafields'     => array_merge(
                $this->convertMetafields(GraphQLHelper::extractGraphQLEdges($skuData, 'commerceAppsMetafields')),
                $this->convertMetafields(GraphQLHelper::extractGraphQLEdges($skuData, 'catalogV1Metafields'))
            ),
        ]);
    }

    /**
     * Converts GraphQL metafields node data to Metafield objects.
     *
     * @param array<mixed> $metafieldNodes Array of metafield node data (edges already extracted)
     * @return Metafield[]
     */
    protected function convertMetafields(array $metafieldNodes) : array
    {
        return array_map(function ($node) {
            $nodeData = TypeHelper::arrayOfStringsAsKeys($node);

            return new Metafield([
                'namespace' => TypeHelper::string(ArrayHelper::get($nodeData, 'namespace'), ''),
                'key'       => TypeHelper::string(ArrayHelper::get($nodeData, 'key'), ''),
                'value'     => TypeHelper::string(ArrayHelper::get($nodeData, 'value'), ''),
                'type'      => TypeHelper::string(ArrayHelper::get($nodeData, 'type'), ''),
            ]);
        }, $metafieldNodes);
    }

    /**
     * Creates an InventoryCount object from GraphQL node data and a Sku object.
     *
     * @param array<string, mixed> $inventoryCountData
     * @param Sku $sku
     * @return InventoryCount
     */
    protected function makeInventoryCountObjectFromNode(array $inventoryCountData, Sku $sku) : InventoryCount
    {
        return new InventoryCount([
            'id'         => TypeHelper::string(ArrayHelper::get($inventoryCountData, 'id', ''), ''),
            'quantity'   => TypeHelper::int(ArrayHelper::get($inventoryCountData, 'quantity', 0), 0),
            'onHand'     => TypeHelper::int(ArrayHelper::get($inventoryCountData, 'onHand', 0), 0),
            'type'       => TypeHelper::string(ArrayHelper::get($inventoryCountData, 'type', ''), ''),
            'sku'        => $sku,
            'locationId' => TypeHelper::string(ArrayHelper::get($inventoryCountData, 'location.id', ''), ''),
            'createdAt'  => TypeHelper::string(ArrayHelper::get($inventoryCountData, 'createdAt', ''), ''),
            'updatedAt'  => TypeHelper::string(ArrayHelper::get($inventoryCountData, 'updatedAt', ''), ''),
        ]);
    }

    /**
     * Extracts and converts inventory counts from GraphQL response data.
     *
     * @param ResponseContract $response
     * @param string $dataPath The path to the inventory counts data in the response (e.g., 'data.inventoryCount' or 'data.inventoryCounts')
     * @return InventoryCount[]|null
     */
    protected function extractInventoryCountsFromResponse(ResponseContract $response, string $dataPath) : ?array
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $inventoryCountsData = ArrayHelper::get($responseBody, $dataPath, []);

        if (empty($inventoryCountsData)) {
            return null;
        }

        $inventoryCounts = [];
        foreach (TypeHelper::array($inventoryCountsData, []) as $inventoryCountData) {
            $inventoryCounts[] = $this->convertInventoryCountFromGraphQLData(
                TypeHelper::arrayOfStringsAsKeys($inventoryCountData)
            );
        }

        return $inventoryCounts ?: null;
    }
}
