<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts\InventoryCountsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ListInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ListInventoryCountOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryCountOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts\ListInventoryCountRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryCounts\ReadInventoryCountRequestAdapter;

class InventoryCountsGateway extends AbstractGateway implements InventoryCountsGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /** {@inheritDoc} */
    public function read(ReadInventoryCountInput $input) : ReadInventoryCountOutput
    {
        /** @var ReadInventoryCountOutput $result */
        $result = $this->doAdaptedRequest(ReadInventoryCountRequestAdapter::getNewInstance($input));

        return $result;
    }

    /** {@inheritDoc} */
    public function list(ListInventoryCountInput $input) : ListInventoryCountOutput
    {
        /** @var ListInventoryCountOutput $result */
        $result = $this->doAdaptedRequest(ListInventoryCountRequestAdapter::getNewInstance($input));

        return $result;
    }
}
