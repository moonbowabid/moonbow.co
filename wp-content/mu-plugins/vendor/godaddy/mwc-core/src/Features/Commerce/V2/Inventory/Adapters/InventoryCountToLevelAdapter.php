<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanExtractMetafieldValuesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

class InventoryCountToLevelAdapter
{
    use CanGetNewInstanceTrait;
    use CanExtractMetafieldValuesTrait;

    /**
     * Converts an array of InventoryCount objects to a single Level object.
     *
     * @param InventoryCount[] $inventoryCounts
     * @return Level
     * @throws Exception
     */
    public function convert(array $inventoryCounts) : Level
    {
        $availableCount = $this->getInventoryCountOfType($inventoryCounts, InventoryCountType::Available);
        $backorderedCount = $this->getInventoryCountOfType($inventoryCounts, InventoryCountType::Backordered);

        $sku = $this->getSku($inventoryCounts);
        $backorderLimit = $sku ? $sku->backorderLimit : null;
        $createdAt = $availableCount && $availableCount->createdAt ? new DateTime($availableCount->createdAt) : null;
        $updatedAt = $availableCount && $availableCount->updatedAt ? new DateTime($availableCount->updatedAt) : null;

        // on-hand is the same value regardless of inventory count type, so we can pull it from any available count
        $onHand = 0;
        if ($availableCount) {
            $onHand = $availableCount->onHand ?? 0;
        } elseif ($backorderedCount) {
            $onHand = $backorderedCount->onHand ?? 0;
        } elseif (! empty($inventoryCounts[0])) {
            $onHand = $inventoryCounts[0]->onHand ?? 0;
        }

        $summary = new Summary([
            'inventorySummaryId'    => $availableCount->id ?? null,
            'productId'             => $sku ? $sku->id : '',
            'totalAvailable'        => (float) ($availableCount->quantity ?? 0),
            'totalOnHand'           => (float) $onHand,
            'totalBackordered'      => (float) ($backorderedCount->quantity ?? 0),
            'maxBackorders'         => $backorderLimit !== null ? TypeHelper::float($backorderLimit, 0.00) : null,
            'isBackorderable'       => $backorderLimit !== 0,
            'lowInventoryThreshold' => $this->extractLowInventoryThreshold($sku),
            'createdAt'             => $createdAt,
            'updatedAt'             => $updatedAt,
        ]);

        return new Level([
            'inventoryLevelId'    => $availableCount->id ?? null,
            'inventorySummaryId'  => $availableCount->id ?? '',
            'inventoryLocationId' => $availableCount->locationId ?? '',
            'productId'           => $sku ? $sku->id : '',
            'quantity'            => $onHand,
            'cost'                => null,
            'summary'             => $summary,
            'createdAt'           => $createdAt,
            'updatedAt'           => $updatedAt,
        ]);
    }

    /**
     * Finds a Sku from an array of InventoryCount objects.
     *
     * @param InventoryCount[] $inventoryCounts
     * @return Sku|null
     */
    protected function getSku(array $inventoryCounts) : ?Sku
    {
        foreach ($inventoryCounts as $count) {
            if (isset($count->sku)) {
                return $count->sku;
            }
        }

        return null;
    }

    /**
     * Finds an InventoryCount of a specific type from an array of InventoryCount objects.
     *
     * @param InventoryCount[] $inventoryCounts
     * @param string $type {@see InventoryCountType}
     * @return InventoryCount|null
     */
    protected function getInventoryCountOfType(array $inventoryCounts, string $type) : ?InventoryCount
    {
        foreach ($inventoryCounts as $count) {
            if ($count->type === $type) {
                return $count;
            }
        }

        return null;
    }
}
