<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;

/**
 * Contract for SKUs gateways.
 */
interface SkusGatewayContract
{
    /**
     * Creates a SKU.
     *
     * @param CreateSkuInput $input
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract
     */
    public function create(CreateSkuInput $input) : SkuRequestOutput;

    /**
     * Updates a SKU.
     *
     * @param UpdateSkuInput $input
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract
     */
    public function update(UpdateSkuInput $input) : SkuRequestOutput;

    /**
     * Gets a SKU.
     *
     * @param GetSkuInput $input
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract
     */
    public function get(GetSkuInput $input) : SkuRequestOutput;

    /**
     * Updates SKU relationships.
     *
     * @param UpdateSkuRelationshipsInput $input
     * @throws CommerceExceptionContract
     */
    public function updateRelationships(UpdateSkuRelationshipsInput $input) : void;
}
