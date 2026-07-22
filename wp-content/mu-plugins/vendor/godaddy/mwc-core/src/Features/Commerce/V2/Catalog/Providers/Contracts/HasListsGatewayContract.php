<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

/**
 * Contract for providers that have a lists gateway.
 */
interface HasListsGatewayContract
{
    /**
     * Gets the lists gateway.
     *
     * @return ListsGatewayContract
     */
    public function lists() : ListsGatewayContract;
}
