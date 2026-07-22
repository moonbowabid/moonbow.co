<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

/**
 * Contract for providers that have a list tree nodes gateway.
 */
interface HasListTreeNodesGatewayContract
{
    /**
     * Gets the list tree nodes gateway.
     *
     * @return ListTreeNodesGatewayContract
     */
    public function listTreeNodes() : ListTreeNodesGatewayContract;
}
