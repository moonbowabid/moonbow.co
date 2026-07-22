<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\SkusGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\CreateSkuRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\GetSkuRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\UpdateSkuRelationshipsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\UpdateSkuRequestAdapter;

/**
 * Gateway for handling SKU operations with the V2 API.
 */
class SkusGateway extends AbstractGateway implements SkusGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * Creates a SKU.
     *
     * @param CreateSkuInput $input
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract
     */
    public function create(CreateSkuInput $input) : SkuRequestOutput
    {
        /** @var SkuRequestOutput $result */
        $result = $this->doAdaptedRequest(CreateSkuRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a SKU.
     *
     * @param UpdateSkuInput $input
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract
     */
    public function update(UpdateSkuInput $input) : SkuRequestOutput
    {
        /** @var SkuRequestOutput $result */
        $result = $this->doAdaptedRequest(UpdateSkuRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Gets a SKU.
     *
     * @param GetSkuInput $input
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract
     */
    public function get(GetSkuInput $input) : SkuRequestOutput
    {
        /** @var SkuRequestOutput $result */
        $result = $this->doAdaptedRequest(GetSkuRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates SKU relationships.
     *
     * @param UpdateSkuRelationshipsInput $input
     * @throws CommerceExceptionContract
     */
    public function updateRelationships(UpdateSkuRelationshipsInput $input) : void
    {
        $this->doAdaptedRequest(UpdateSkuRelationshipsRequestAdapter::getNewInstance($input));
    }
}
