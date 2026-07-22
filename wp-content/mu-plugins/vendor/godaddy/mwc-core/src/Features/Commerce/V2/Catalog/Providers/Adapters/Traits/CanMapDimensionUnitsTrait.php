<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

/**
 * Trait for normalising free-form WooCommerce dimension unit strings to the canonical short forms accepted by the Catalog API.
 */
trait CanMapDimensionUnitsTrait
{
    /** @var array<string, string> alias (lowercased, trimmed) to canonical Catalog dimension unit */
    protected static array $dimensionUnitAliases = [
        'cm'          => 'cm',
        'centimeter'  => 'cm',
        'centimeters' => 'cm',
        'centimetre'  => 'cm',
        'centimetres' => 'cm',
        'm'           => 'm',
        'meter'       => 'm',
        'meters'      => 'm',
        'metre'       => 'm',
        'metres'      => 'm',
        'mm'          => 'mm',
        'millimeter'  => 'mm',
        'millimeters' => 'mm',
        'millimetre'  => 'mm',
        'millimetres' => 'mm',
        'in'          => 'in',
        'inch'        => 'in',
        'inches'      => 'in',
        '"'           => 'in',
        'ft'          => 'ft',
        'foot'        => 'ft',
        'feet'        => 'ft',
        "'"           => 'ft',
        'yd'          => 'yd',
        'yard'        => 'yd',
        'yards'       => 'yd',
    ];

    /**
     * Normalise a WooCommerce dimension unit string to one of the Catalog API's canonical values.
     *
     * @param string|null $wooDimensionUnit
     * @return string|null canonical unit (cm, m, mm, in, ft, yd) or null if not recognised
     */
    protected function mapDimensionUnitToSku(?string $wooDimensionUnit) : ?string
    {
        if ($wooDimensionUnit === null) {
            return null;
        }

        $normalised = strtolower(trim($wooDimensionUnit));

        if ($normalised === '') {
            return null;
        }

        return static::$dimensionUnitAliases[$normalised] ?? null;
    }
}
