<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\LineItemReservedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;
use WC_Product;

abstract class AbstractOrderReservationsService implements OrderReservationsServiceContract
{
    const TRANSIENT_RESERVATIONS_FAILED = 'godaddy_mwc_commerce_reservations_failed';

    protected ProductInventoryCachingServiceContract $inventoryCachingService;
    protected WooOrderCartIdProvider $cartIdProvider;

    public function __construct(
        ProductInventoryCachingServiceContract $inventoryCachingService,
        WooOrderCartIdProvider $cartIdProvider
    ) {
        $this->inventoryCachingService = $inventoryCachingService;
        $this->cartIdProvider = $cartIdProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateReservations(Order &$order) : void
    {
        $reservations = $notes = [];

        foreach ($order->getLineItems() as $lineItem) {
            try {
                if (! $product = $lineItem->getProduct()) {
                    continue;
                }

                if (! $product->managing_stock()) {
                    continue;
                }

                $lineReservations = $this->createOrUpdateReservationInRemoteService($product, $lineItem, $order);

                Events::broadcast(new LineItemReservedEvent($lineItem, $lineReservations, $order));

                array_push($reservations, ...$lineReservations);
            } catch (MissingProductRemoteIdException $exception) {
                // No-op. This is expected for a site that has not pushed all its products up to commerce platform.
                // The order should be allowed to proceed as if there was inventory available. See MWC-12739.
            } catch (Exception|CommerceExceptionContract $exception) {
                $this->markOrderReservationsFailed($order);

                $notes[] = Note::seed([
                    'authorName' => Note::SYSTEM_AUTHOR_NAME,
                    'content'    => sprintf(
                        /* translators: Placeholders: %1$s - a product name, %2$s - a product SKU, %3$s - an API error message */
                        __('An error occurred while reserving stock for %1$s (%2$s). %3$s', 'mwc-core'),
                        $lineItem->getName(),
                        $lineItem->getSku(),
                        $exception->getMessage()
                    ),
                ]);
            }
        }

        // refresh the inventory cache for all products that were reserved
        $this->refreshCache($reservations);

        // add any failure notes to the order
        $order->addNotes(...$notes);
    }

    /**
     * Handles creating or updating the reservation in the remote service for the provided line item.
     *
     * @param WC_Product $product
     * @param LineItem $lineItem
     * @param Order $order
     * @return Reservation[]
     * @throws Exception|CommerceExceptionContract
     */
    abstract protected function createOrUpdateReservationInRemoteService(WC_Product $product, LineItem $lineItem, Order $order) : array;

    /**
     * Refreshes the product inventory caches for the given reservations.
     *
     * @param Reservation[] $reservations
     */
    protected function refreshCache(array $reservations) : void
    {
        if ($productIds = array_unique(TypeHelper::arrayOfStrings(array_map(static fn (Reservation $reservation) => $reservation->productId, $reservations)))) {
            $this->inventoryCachingService->refreshCache($productIds);
        }
    }

    /**
     * Marks an order as having failed reservations.
     *
     * @param Order $order
     */
    public function markOrderReservationsFailed(Order $order) : void
    {
        set_transient(static::getReservationsFailedTransientKey($order->getCartId()), 'yes', 10);
    }

    /**
     * {@inheritDoc}
     */
    public function orderHasFailedReservations(WC_Order $order) : bool
    {
        return wc_string_to_bool(TypeHelper::string(get_transient(static::getReservationsFailedTransientKey($this->cartIdProvider->getCartId($order))), ''));
    }

    /**
     * Gets the transient key name for the provided cart ID.
     */
    protected function getReservationsFailedTransientKey(?string $cartId) : string
    {
        $cartId = TypeHelper::string($cartId, '');

        return TypeHelper::string(static::TRANSIENT_RESERVATIONS_FAILED, '').$cartId;
    }
}
