<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Dimensions;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Inventory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ShippingWeightAndDimensions;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Weight;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Metafield;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuPrice;

/**
 * Trait for converting SKU objects to ProductBase.
 *
 * Provides methods to convert SKU data into ProductBase format with all necessary
 * price, inventory, shipping, and metadata conversions.
 */
trait CanConvertSkuToProductBaseTrait
{
    use CanConvertMediaObjectsToAssetsTrait;
    use CanConvertAttributesToOptionsTrait;
    use CanMapProductTypesTrait;
    use CanMapWeightUnitsTrait;
    use CanConvertListsToCategoryIdsTrait;
    use CanExtractMetafieldValuesTrait;

    /**
     * Convert a SKU object to ProductBase.
     *
     * @param Sku $sku
     * @param SkuGroup $skuGroup
     * @return ProductBase
     */
    public function convertSkuToProductBase(Sku $sku, SkuGroup $skuGroup) : ProductBase
    {
        // Convert SKU prices to SimpleMoney objects for ProductBase
        $price = $this->extractRegularPriceFromSkuPrices($sku->prices);
        $salePrice = $this->extractSalePriceFromSkuPrices($sku->prices);

        // Map SKU type to ProductBase type
        $productType = $this->mapCatalogTypeToProductType($skuGroup->type);

        // Determine if this is a variation based on parent relationships or attributes
        $isVariation = ! empty($sku->attributeValues);

        return new ProductBase([
            // Basic product information from SKU Group (parent level data)
            'active'                      => $this->mapCatalogStatusToActive($sku->status),
            'altId'                       => $sku->name, // Use SKU name as alternative ID
            'allowCustomPrice'            => null, // Not available
            'assets'                      => $this->convertMediaObjectsToAssets($sku->getMediaObjects()),
            'brand'                       => $this->getMetafieldValue($sku, 'brand'),
            'categoryIds'                 => $this->convertListObjectsToCategoryIds($skuGroup->lists),
            'channelIds'                  => [], // @todo MWC-18585
            'condition'                   => $this->getMetafieldValue($sku, 'condition'),
            'createdAt'                   => $sku->createdAt,
            'description'                 => $sku->htmlDescription ?: $sku->description,
            'ean'                         => $sku->eanCode,
            'externalIds'                 => $this->convertExternalIds($sku),
            'files'                       => null, // v2 does not support files; setting to null ensures we won't adapt "empty files" in ProductPostMetaAdapter
            'inventory'                   => $this->convertInventoryFromSku($sku),
            'lowInventoryThreshold'       => $this->extractLowInventoryThreshold($sku),
            'manufacturerData'            => null, // Not available
            'name'                        => $sku->label,
            'options'                     => $this->convertOptionsFromSku($sku),
            'parentId'                    => $isVariation ? $sku->skuGroupId : null,
            'price'                       => $price,
            'productId'                   => $sku->id,
            'purchasable'                 => $sku->status === 'ACTIVE',
            'salePrice'                   => $salePrice,
            'shippingWeightAndDimensions' => $this->extractShippingWeightAndDimensions($sku),
            'shortCode'                   => null, // Not available
            'sku'                         => $sku->code,
            'taxCategory'                 => $this->getMetafieldValue($sku, 'taxCategory'),
            'type'                        => $productType,
            'updatedAt'                   => $sku->updatedAt,
            'variantOptionMapping'        => $isVariation ? $this->convertVariantOptionMappingFromSku($sku) : null,
            'variants'                    => null,
        ]);
    }

    /**
     * Extract regular price from SKU prices.
     * If compareAtValue exists, use it as the regular price, otherwise use value.
     *
     * @param SkuPrice[] $skuPrices Array of SkuPrice objects
     * @return SimpleMoney|null
     */
    protected function extractRegularPriceFromSkuPrices(array $skuPrices) : ?SimpleMoney
    {
        if (empty($skuPrices)) {
            return null;
        }

        $firstPrice = $skuPrices[0] ?? null;
        if (! $firstPrice) {
            return null;
        }

        // If there's a compareAtValue, that's the regular price
        // Otherwise, the value is the regular price
        return $firstPrice->compareAtValue ?: $firstPrice->value;
    }

    /**
     * Extract sale price from SKU prices if compareAtValue is present.
     * Sale price is the value field when compareAtValue exists.
     *
     * @param SkuPrice[] $skuPrices Array of SkuPrice objects
     * @return SimpleMoney|null
     */
    protected function extractSalePriceFromSkuPrices(array $skuPrices) : ?SimpleMoney
    {
        if (empty($skuPrices)) {
            return null;
        }

        $firstPrice = $skuPrices[0] ?? null;
        if (! $firstPrice || ! $firstPrice->compareAtValue) {
            return null;
        }

        // Sale price is the value when compareAtValue exists
        return $firstPrice->value;
    }

    /**
     * Convert SKU external identifiers to ProductBase format.
     *
     * @param Sku $sku
     * @return ExternalId[]
     */
    protected function convertExternalIds(Sku $sku) : array
    {
        $externalIds = [];

        // Add UPC code if available
        if (! empty($sku->upcCode)) {
            $externalIds[] = ExternalId::getNewInstance([
                'type'  => ExternalId::TYPE_UPC,
                'value' => $sku->upcCode,
            ]);
        }

        // Add GTIN code if available
        if (! empty($sku->gtinCode)) {
            $externalIds[] = ExternalId::getNewInstance([
                'type'  => ExternalId::TYPE_GTIN,
                'value' => $sku->gtinCode,
            ]);
        }

        return $externalIds;
    }

    /**
     * Extracts ShippingWeightAndDimensions from SKU properties and metafields.
     *
     * Weight comes from first-class Sku properties (sku.weight, sku.unitOfWeight).
     * Dimensions come from the shippingWeightAndDimensions.dimensions metafield.
     *
     * @param Sku $sku
     * @return ShippingWeightAndDimensions|null
     */
    protected function extractShippingWeightAndDimensions(Sku $sku) : ?ShippingWeightAndDimensions
    {
        // Weight always comes from first-class Sku properties, not metafields
        if ($sku->weight === null) {
            return null; // Weight is required
        }

        $weight = Weight::getNewInstance([
            'value' => $sku->weight,
            'unit'  => $this->mapWeightUnitFromSku(TypeHelper::string($sku->unitOfWeight, 'lbs')),
        ]);

        // Dimensions come from metafield (optional)
        $dimensions = $this->extractDimensions($sku);

        return new ShippingWeightAndDimensions([
            'weight'     => $weight,
            'dimensions' => $dimensions,
        ]);
    }

    /**
     * Extracts dimensions from SKU metafields.
     *
     * The API uses "shippingWeightAndDimensions.dimensions" as the metafield key (dot is part of the key).
     * The JSON value contains unit, length, width, and height directly (not nested).
     *
     * @param Sku $sku
     * @return Dimensions|null
     */
    protected function extractDimensions(Sku $sku) : ?Dimensions
    {
        $dimensionsData = $this->getMetafieldValue($sku, 'shippingWeightAndDimensions.dimensions');

        if (! $dimensionsData) {
            return null;
        }

        $data = json_decode($dimensionsData, true);

        if (! is_array($data)) {
            return null;
        }

        $length = ArrayHelper::get($data, 'length');
        $width = ArrayHelper::get($data, 'width');
        $height = ArrayHelper::get($data, 'height');
        $unit = ArrayHelper::get($data, 'unit');

        $length = $length !== null ? TypeHelper::float($length, 0.0) : null;
        $width = $width !== null ? TypeHelper::float($width, 0.0) : null;
        $height = $height !== null ? TypeHelper::float($height, 0.0) : null;

        // All three dimension values are required if dimensions are present
        if ($length === null || $width === null || $height === null) {
            return null;
        }

        return Dimensions::getNewInstance([
            'length' => $length,
            'width'  => $width,
            'height' => $height,
            'unit'   => TypeHelper::string($unit, 'in'),
        ]);
    }

    /**
     * Convert SKU inventory data to Inventory object.
     *
     * @param Sku $sku
     * @return Inventory|null
     */
    protected function convertInventoryFromSku(Sku $sku) : ?Inventory
    {
        return new Inventory([
            'tracking' => ! $sku->disableInventoryTracking,
        ]);
    }
}
