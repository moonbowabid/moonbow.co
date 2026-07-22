<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Adapters;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;

/**
 * Class for converting a v2 {@see InventoryAdjustment} object into a corresponding v1 {@see Reservation} object.
 */
class InventoryAdjustmentToReservationAdapter
{
    use CanGetNewInstanceTrait;

    /**
     * @throws AdapterException
     */
    public function convert(InventoryAdjustment $adjustment) : Reservation
    {
        return new Reservation([
            'inventoryReservationId' => $adjustment->id,
            'inventoryLocationId'    => $adjustment->locationId,
            'quantity'               => abs($adjustment->delta), // Use absolute value as reservation quantity
            'productId'              => $adjustment->sku->id,
            'type'                   => $this->convertType($adjustment->type),
            'status'                 => 'LOCKED', // in v2 adjustments are immutable so we'll always consider them locked
            'expiresAt'              => null, // V2 commitments don't have expiration
            'externalIds'            => $this->convertReferencesToExternalIds($adjustment->references),
        ]);
    }

    /**
     * Converts v2 adjustment types to v1 reservation types.
     * For v1 types see @link https://inventory.commerce.godaddy.com/inventory/api-docs.
     * @throws AdapterException
     */
    protected function convertType(string $adjustmentType) : string
    {
        if ($adjustmentType === InventoryCountType::Committed) {
            return 'RESERVED';
        } elseif ($adjustmentType === InventoryCountType::Backordered) {
            return 'BACKORDERED';
        } else {
            // Other options would be `AVAILABLE`, but v1 doesn't track those, which is why we'd throw an exception here
            throw new AdapterException("Unexpected adjustment type: {$adjustmentType}");
        }
    }

    /**
     * Converts an array of v2 references to an array of v1 external IDs.
     *
     * @param array<Reference> $references
     * @return array<ExternalId>
     */
    protected function convertReferencesToExternalIds(array $references) : array
    {
        $externalIds = [];

        foreach ($references as $reference) {
            $externalIds[] = new ExternalId([
                'type'  => TypeHelper::string($reference->origin, ''),
                'value' => TypeHelper::string($reference->value, ''),
            ]);
        }

        return $externalIds;
    }
}
