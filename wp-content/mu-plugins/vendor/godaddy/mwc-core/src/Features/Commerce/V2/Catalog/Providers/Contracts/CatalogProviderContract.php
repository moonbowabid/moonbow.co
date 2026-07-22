<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

/**
 * V2 Catalog provider contract.
 */
interface CatalogProviderContract extends HasListsGatewayContract, HasSkuGroupsGatewayContract, HasSkusGatewayContract, HasListTreeNodesGatewayContract
{
}
