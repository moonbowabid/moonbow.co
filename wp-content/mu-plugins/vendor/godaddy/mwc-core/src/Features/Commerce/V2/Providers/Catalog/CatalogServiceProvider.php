<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\ListTreeNodesGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\SkuGroupsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\SkusGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\CatalogProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways\ListTreeNodesGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways\SkuGroupsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways\SkusGateway;

class CatalogServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        CatalogProviderContract::class,
        ListTreeNodesGatewayContract::class,
        SkuGroupsGatewayContract::class,
        SkusGatewayContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CatalogProviderContract::class, CatalogProvider::class);
        $this->getContainer()->singleton(ListTreeNodesGatewayContract::class, ListTreeNodesGateway::class);
        $this->getContainer()->singleton(SkuGroupsGatewayContract::class, SkuGroupsGateway::class);
        $this->getContainer()->singleton(SkusGatewayContract::class, SkusGateway::class);
    }
}
