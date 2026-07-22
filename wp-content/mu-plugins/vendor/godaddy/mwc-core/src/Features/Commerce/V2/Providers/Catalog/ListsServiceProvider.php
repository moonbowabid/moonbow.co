<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\Catalog;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\ListsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways\ListsGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Contracts\ListsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListsMappingService;

class ListsServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        ListsMappingServiceContract::class,
        ListsGatewayContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(ListsMappingServiceContract::class, ListsMappingService::class);
        $this->getContainer()->singleton(ListsGatewayContract::class, ListsGateway::class);
    }
}
