<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\OrderReservationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\NoopOrderReservationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services\OrderReservationsService as V2OrderReservationsService;

class OrderReservationsServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrderReservationsServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        if (InventoryIntegration::isEnabled()) {
            if (CatalogIntegration::shouldUseV2Api()) {
                $concrete = V2OrderReservationsService::class;
            } else {
                $concrete = OrderReservationsService::class;
            }
        } else {
            $concrete = NoopOrderReservationsService::class;
        }

        $this->getContainer()->bind(OrderReservationsServiceContract::class, $concrete);
    }
}
