<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\CreateOrUpdateReservationOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Product;

class OrderReservationsService extends AbstractOrderReservationsService
{
    protected ReservationsServiceContract $reservationsService;

    /**
     * @param ReservationsServiceContract $reservationsService
     * @param ProductInventoryCachingServiceContract $inventoryCachingService
     * @param WooOrderCartIdProvider $cartIdProvider
     */
    public function __construct(
        ReservationsServiceContract $reservationsService,
        ProductInventoryCachingServiceContract $inventoryCachingService,
        WooOrderCartIdProvider $cartIdProvider
    ) {
        $this->reservationsService = $reservationsService;

        parent::__construct($inventoryCachingService, $cartIdProvider);
    }

    /**
     * {@inheritDoc}
     */
    protected function createOrUpdateReservationInRemoteService(WC_Product $product, LineItem $lineItem, Order $order) : array
    {
        return $this->reservationsService->createOrUpdateReservation(new CreateOrUpdateReservationOperation($lineItem, $order))->getReservations();
    }
}
