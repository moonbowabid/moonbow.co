<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\PriceUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductPricesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuPrice;

/**
 * Builder for price updates between products and SKUs.
 *
 * Since SKUs being updated always have prices, this builder updates existing prices
 * directly using the updateSkuPrice mutation instead of remove/add operations.
 */
class PriceUpdatesBuilder
{
    use CanConvertProductPricesTrait;

    /**
     * Build price updates for a SKU based on product operation.
     *
     * Since SKUs being updated always have prices, we can directly update existing prices.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param Sku $sku
     * @return PriceUpdates|null
     */
    public function build(CreateOrUpdateProductOperationContract $operation, Sku $sku) : ?PriceUpdates
    {
        $priceUpdates = $this->createPriceUpdates();

        if ($toUpdate = $this->buildUpdatedPrice($operation, $sku)) {
            $priceUpdates->toUpdate = $toUpdate;
        }

        return $priceUpdates->hasUpdates() ? $priceUpdates : null;
    }

    /**
     * Build the updated price with new local data and existing price ID.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param Sku $sku
     * @return SkuPrice|null
     */
    protected function buildUpdatedPrice(CreateOrUpdateProductOperationContract $operation, Sku $sku) : ?SkuPrice
    {
        $currentPrices = $sku->prices;
        if (empty($currentPrices)) {
            return null;
        }

        // Take the first price
        // Having multiple prices is technically possible at an API level but not yet supported
        // Using "the first price" is the recommended approach
        // @link https://godaddy-corp.atlassian.net/wiki/spaces/MWCENG/pages/3817412470/2025-05-29+Discovery+MWC+Implement+Catalog+API+V2?focusedCommentId=3916271059
        $existingPrice = $currentPrices[0] ?? null;
        if (empty($existingPrice->id)) {
            return null;
        }

        // Get new local price data from the product
        $product = $operation->getProduct();
        $localPrices = $this->convertProductPrices(
            $product->getRegularPrice(),
            $product->getSalePrice()
        );

        if (empty($localPrices) || ! isset($localPrices[0])) {
            return null;
        }

        $localPrice = $localPrices[0];

        // Return null if existing price and local price have the same values
        if ($this->pricesAreEqual($existingPrice, $localPrice)) {
            return null;
        }

        return SkuPrice::getNewInstance([
            'id'             => $existingPrice->id,
            'value'          => $localPrice->value,
            'compareAtValue' => $localPrice->compareAtValue,
        ]);
    }

    /**
     * Compare two SkuPrice objects to determine if they have the same values.
     *
     * @param SkuPrice $existingPrice
     * @param SkuPrice $localPrice
     * @return bool
     */
    protected function pricesAreEqual(SkuPrice $existingPrice, SkuPrice $localPrice) : bool
    {
        // Compare main price values
        if (! $this->moneyValuesAreEqual($existingPrice->value, $localPrice->value)) {
            return false;
        }

        // Compare compareAtValue (both can be null)
        if ($existingPrice->compareAtValue === null && $localPrice->compareAtValue === null) {
            return true;
        }

        if ($existingPrice->compareAtValue === null || $localPrice->compareAtValue === null) {
            return false;
        }

        return $this->moneyValuesAreEqual($existingPrice->compareAtValue, $localPrice->compareAtValue);
    }

    /**
     * Compare two SimpleMoney objects to determine if they have the same values.
     *
     * @param SimpleMoney $money1
     * @param SimpleMoney $money2
     * @return bool
     */
    protected function moneyValuesAreEqual(SimpleMoney $money1, SimpleMoney $money2) : bool
    {
        return $money1->currencyCode === $money2->currencyCode && $money1->value === $money2->value;
    }

    /**
     * Create a new PriceUpdates instance.
     *
     * @return PriceUpdates
     */
    protected function createPriceUpdates() : PriceUpdates
    {
        return new PriceUpdates();
    }
}
