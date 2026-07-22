<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

/**
 * Contract for providers that have a SKUs gateway.
 */
interface HasSkusGatewayContract
{
    /**
     * Gets the SKUs gateway.
     *
     * @return SkusGatewayContract
     */
    public function skus() : SkusGatewayContract;
}
