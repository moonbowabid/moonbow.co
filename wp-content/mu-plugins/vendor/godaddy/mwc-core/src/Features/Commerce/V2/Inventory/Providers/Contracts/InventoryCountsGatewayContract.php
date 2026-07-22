<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ListInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ListInventoryCountOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryCountOutput;

/**
 * Contract for inventory count gateways.
 */
interface InventoryCountsGatewayContract
{
    /**
     * Reads the current inventory count for a single product (sku).
     *
     * @throws CommerceExceptionContract
     */
    public function read(ReadInventoryCountInput $input) : ReadInventoryCountOutput;

    /**
     * Lists inventory counts for multiple products (skus).
     *
     * @throws CommerceExceptionContract
     */
    public function list(ListInventoryCountInput $input) : ListInventoryCountOutput;
}
