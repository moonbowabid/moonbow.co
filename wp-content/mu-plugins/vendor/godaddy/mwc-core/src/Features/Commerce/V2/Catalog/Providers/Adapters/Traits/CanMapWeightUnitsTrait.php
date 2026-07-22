<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

/**
 * Trait for mapping weight units between different formats.
 */
trait CanMapWeightUnitsTrait
{
    /** @var array<string, string> SKU weight unit to product weight unit mapping */
    protected static array $skuToProductUnits = [
        'KG' => 'kg',
        'GR' => 'g',
        'LB' => 'lbs',
        'OZ' => 'oz',
    ];

    /**
     * Map weight unit from SKU format to ProductBase format.
     *
     * @param string $skuWeightUnit
     * @return string
     */
    protected function mapWeightUnitFromSku(string $skuWeightUnit) : string
    {
        return static::$skuToProductUnits[strtoupper($skuWeightUnit)] ?? strtolower($skuWeightUnit);
    }

    /**
     * Map weight unit from Product format to SKU format.
     *
     * @param string|null $productWeightUnit
     * @return string|null
     */
    protected function mapWeightUnitToSku(?string $productWeightUnit) : ?string
    {
        if (empty($productWeightUnit)) {
            return null;
        }

        $productToSkuUnits = array_flip(static::$skuToProductUnits);

        return $productToSkuUnits[strtolower($productWeightUnit)] ?? null;
    }
}
