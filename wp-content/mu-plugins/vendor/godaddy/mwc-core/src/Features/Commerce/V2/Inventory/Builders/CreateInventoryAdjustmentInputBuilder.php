<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Builders;

use DateTimeImmutable;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CreateInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Builder for creating inventory adjustment input objects based on current vs desired stock levels.
 *
 * @method static static getNewInstance(string $storeId, string $skuId, string $locationId)
 */
class CreateInventoryAdjustmentInputBuilder
{
    use CanGetNewInstanceTrait;

    protected string $storeId;
    protected string $skuId;
    protected string $locationId;

    public function __construct(string $storeId, string $skuId, string $locationId)
    {
        $this->storeId = $storeId;
        $this->skuId = $skuId;
        $this->locationId = $locationId;
    }

    /**
     * Creates an adjustment input based on desired stock level and current inventory counts.
     *
     * Context: the `$desiredOnHand` is the "on hand" stock level we want to achieve. The API doesn't allow you to
     * change the "on hand" level directly; instead it's auto calculated as `available + committed`. So to achieve
     * the desired "on hand" level, we need to adjust the "available" count accordingly, taking into account any
     * "committed" stock that is already reserved.
     *
     * @param Product $product
     * @param InventoryCount[] $currentInventoryCounts
     * @return CreateInventoryAdjustmentInput|null Returns null if no adjustment is needed
     */
    public function build(Product $product, array $currentInventoryCounts) : ?CreateInventoryAdjustmentInput
    {
        $desiredOnHand = (int) ($product->getCurrentStock() ?? 0);
        $currentCounts = $this->groupInventoryCountsByType($currentInventoryCounts);

        // Calculate what the AVAILABLE count should be to achieve desired onHand
        $currentCommitted = $currentCounts[InventoryCountType::Committed] ?? 0;
        $newAvailable = $desiredOnHand - $currentCommitted;

        // Calculate delta for AVAILABLE type
        $currentAvailable = $currentCounts[InventoryCountType::Available] ?? 0;
        $delta = (int) ($newAvailable - $currentAvailable);

        // Return null if no adjustment needed
        if ($delta === 0) {
            return null;
        }

        return new CreateInventoryAdjustmentInput([
            'storeId'    => $this->storeId,
            'delta'      => $delta,
            'locationId' => $this->locationId,
            'skuId'      => $this->skuId,
            'type'       => InventoryCountType::Available,
            'occurredAt' => new DateTimeImmutable(),
        ]);
    }

    /**
     * Groups inventory counts by type for easy lookup.
     *
     * @param InventoryCount[] $inventoryCounts
     * @return array<string, int> Map of type => quantity
     */
    protected function groupInventoryCountsByType(array $inventoryCounts) : array
    {
        $countsByType = [];
        foreach ($inventoryCounts as $count) {
            $countsByType[$count->type] = $count->quantity;
        }

        return $countsByType;
    }
}
