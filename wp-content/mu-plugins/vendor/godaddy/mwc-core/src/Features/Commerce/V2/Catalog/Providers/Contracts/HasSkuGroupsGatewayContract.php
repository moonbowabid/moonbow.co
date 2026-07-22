<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

/**
 * Contract for providers that have a SKU groups gateway.
 */
interface HasSkuGroupsGatewayContract
{
    /**
     * Gets the SKU groups gateway.
     *
     * @return SkuGroupsGatewayContract
     */
    public function skuGroups() : SkuGroupsGatewayContract;
}
