<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\SimpleMoneyAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuPrice;

/**
 * Trait for converting WooCommerce product prices to SkuPrice objects.
 *
 * Provides shared logic for converting product regular and sale prices
 * into the appropriate SimpleMoney and SkuPrice format for the v2 API.
 */
trait CanConvertProductPricesTrait
{
    /**
     * Convert WooCommerce product prices to SkuPrice objects.
     *
     * @param CurrencyAmount|null $regularPrice
     * @param CurrencyAmount|null $salePrice
     * @return SkuPrice[]
     */
    protected function convertProductPrices(?CurrencyAmount $regularPrice, ?CurrencyAmount $salePrice) : array
    {
        $regularSimpleMoney = $this->convertPriceToSimpleMoney($regularPrice);
        if (! $regularSimpleMoney) {
            return [];
        }

        $saleSimpleMoney = $this->convertPriceToSimpleMoney($salePrice);
        $priceData = $this->buildSkuPriceData($regularSimpleMoney, $saleSimpleMoney);

        return [SkuPrice::getNewInstance($priceData)];
    }

    /**
     * Convert a CurrencyAmount to SimpleMoney.
     *
     * @param CurrencyAmount|null $price
     * @return SimpleMoney|null
     */
    protected function convertPriceToSimpleMoney(?CurrencyAmount $price) : ?SimpleMoney
    {
        if (! $price) {
            return null;
        }

        $adapter = SimpleMoneyAdapter::getNewInstance();

        return $adapter->convertToSource($price);
    }

    /**
     * Build price data array based on regular and sale prices.
     *
     * @param SimpleMoney $regularSimpleMoney
     * @param SimpleMoney|null $saleSimpleMoney
     * @return array<string, mixed>
     */
    protected function buildSkuPriceData(SimpleMoney $regularSimpleMoney, ?SimpleMoney $saleSimpleMoney) : array
    {
        // If there's a sale price, it becomes the main value and regular becomes compareAt
        if ($saleSimpleMoney) {
            return [
                'value'          => $saleSimpleMoney,
                'compareAtValue' => $regularSimpleMoney,
            ];
        }

        // Otherwise, just use regular price as main value
        return [
            'value' => $regularSimpleMoney,
        ];
    }
}
