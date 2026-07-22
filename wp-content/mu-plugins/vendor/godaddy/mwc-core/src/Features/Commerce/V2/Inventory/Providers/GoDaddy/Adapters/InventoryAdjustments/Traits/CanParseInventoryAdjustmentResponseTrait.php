<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\Sku;

/**
 * Trait for parsing inventory adjustments from a payload.
 */
trait CanParseInventoryAdjustmentResponseTrait
{
    /**
     * Creates an InventoryAdjustment object from GraphQL response data.
     *
     * @param array<string, mixed> $inventoryAdjustmentData
     * @return InventoryAdjustment
     */
    protected function createInventoryAdjustmentFromResponse(array $inventoryAdjustmentData) : InventoryAdjustment
    {
        $skuData = TypeHelper::arrayOfStringsAsKeys(ArrayHelper::get($inventoryAdjustmentData, 'sku', []));
        $sku = $this->makeSkuObjectFromNode($skuData);

        return new InventoryAdjustment([
            'id'         => TypeHelper::string(ArrayHelper::get($inventoryAdjustmentData, 'id', ''), ''),
            'delta'      => TypeHelper::int(ArrayHelper::get($inventoryAdjustmentData, 'delta', 0), 0),
            'type'       => TypeHelper::string(ArrayHelper::get($inventoryAdjustmentData, 'type', ''), ''),
            'occurredAt' => TypeHelper::string(ArrayHelper::get($inventoryAdjustmentData, 'occurredAt', ''), ''),
            'sku'        => $sku,
            'locationId' => TypeHelper::string(ArrayHelper::get($inventoryAdjustmentData, 'location.id', ''), ''),
        ]);
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
        ]);
    }
}
