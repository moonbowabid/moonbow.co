<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;

/**
 * Enum representing types of inventory counts {@see InventoryCount}.
 */
class InventoryCountType
{
    use EnumTrait;

    /** @var string amount available for sale */
    public const Available = 'AVAILABLE';

    /** @var string amount already committed to orders */
    public const Committed = 'COMMITTED';

    /** @var string amount backordered */
    public const Backordered = 'BACKORDERED';
}
