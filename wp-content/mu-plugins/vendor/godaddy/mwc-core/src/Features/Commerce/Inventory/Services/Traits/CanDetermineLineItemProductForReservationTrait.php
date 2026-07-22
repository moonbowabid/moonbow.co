<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

trait CanDetermineLineItemProductForReservationTrait
{
    /**
     * Gets the product for creating/updating a reservation.
     *
     * @param LineItem $lineItem
     *
     * @return Product|null
     * @throws Exception
     */
    protected function getProductForReservation(LineItem $lineItem) : ?Product
    {
        $wooProduct = $lineItem->getProduct();

        // if the stock is managed by another product, get that product instead
        if ($wooProduct && $wooProduct->get_id() !== $wooProduct->get_stock_managed_by_id()) {
            $wooProduct = ProductsRepository::get($wooProduct->get_stock_managed_by_id());
        }

        return $wooProduct ? ProductAdapter::getNewInstance($wooProduct)->convertFromSource() : null;
    }
}
