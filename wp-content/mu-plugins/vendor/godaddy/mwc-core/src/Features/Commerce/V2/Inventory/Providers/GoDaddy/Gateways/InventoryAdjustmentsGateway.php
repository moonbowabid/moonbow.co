<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Adapters\CommitInventoryRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Contracts\InventoryAdjustmentsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CommitInventoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CreateInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\CommitInventoryOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\CreateInventoryAdjustmentOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryAdjustmentOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments\CreateInventoryAdjustmentRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Adapters\InventoryAdjustments\ReadInventoryAdjustmentRequestAdapter;

class InventoryAdjustmentsGateway extends AbstractGateway implements InventoryAdjustmentsGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /** {@inheritDoc} */
    public function read(ReadInventoryAdjustmentInput $input) : ReadInventoryAdjustmentOutput
    {
        /** @var ReadInventoryAdjustmentOutput $result */
        $result = $this->doAdaptedRequest(ReadInventoryAdjustmentRequestAdapter::getNewInstance($input));

        return $result;
    }

    /** {@inheritDoc} */
    public function create(CreateInventoryAdjustmentInput $input) : CreateInventoryAdjustmentOutput
    {
        /** @var CreateInventoryAdjustmentOutput $result */
        $result = $this->doAdaptedRequest(CreateInventoryAdjustmentRequestAdapter::getNewInstance($input));

        return $result;
    }

    /** {@inheritDoc} */
    public function commitForOrder(CommitInventoryInput $input) : CommitInventoryOutput
    {
        /** @var CommitInventoryOutput $result */
        $result = $this->doAdaptedRequest(CommitInventoryRequestAdapter::getNewInstance($input));

        return $result;
    }
}
