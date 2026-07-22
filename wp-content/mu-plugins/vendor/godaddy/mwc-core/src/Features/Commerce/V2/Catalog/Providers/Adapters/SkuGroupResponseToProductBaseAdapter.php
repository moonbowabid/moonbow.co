<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Contracts\RemoteProductToProductBaseAdapterInterface;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertAttributesToOptionsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertListsToCategoryIdsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertMediaObjectsToAssetsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertSkuToProductBaseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanMapProductTypesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Adapter for converting SKU Group objects to ProductBase.
 *
 * Used for variable products that only have SKU Group data (no individual SKU).
 */
class SkuGroupResponseToProductBaseAdapter implements RemoteProductToProductBaseAdapterInterface
{
    use CanConvertMediaObjectsToAssetsTrait;
    use CanConvertAttributesToOptionsTrait;
    use CanMapProductTypesTrait;
    use CanConvertListsToCategoryIdsTrait;
    use CanConvertSkuToProductBaseTrait;

    /**
     * Convert a SKU Group object to ProductBase.
     *
     * @param SkuGroupRequestOutput&object $remoteProductResponse SKU Group object from API response
     * @return ProductBase
     */
    public function convert(object $remoteProductResponse) : ProductBase
    {
        $skuGroup = $remoteProductResponse->skuGroup;

        // Map SKU Group type to ProductBase type
        $productType = $this->mapCatalogTypeToProductType($skuGroup->type);

        // Extract variant SKU codes from the related SKUs
        $variantSkus = [];
        foreach ($skuGroup->skus as $sku) {
            if (! empty($sku->id)) {
                $variantSkus[] = $sku->id;
            }
        }

        return $this->convertSkuGroupToProductBase($skuGroup, $productType, $variantSkus);
    }

    /**
     * Convert a SKU Group object with SKU data to ProductBase.
     *
     * This method should typically be used for retrieving a Simple Product with a skuGroup query.
     *
     * @param SkuGroupRequestOutput&object $remoteProductResponse SKU Group object from API response
     * @return ProductBase
     * @throws AdapterException
     */
    public function convertWithFirstSkuData(object $remoteProductResponse) : ProductBase
    {
        /** @var ?Sku $skuData the first Sku from the skus array. The skuGroup for simple product will only ever have 1 child sku. */
        $skuData = ArrayHelper::get($remoteProductResponse->skus, 0);

        if (! $skuData) {
            throw new AdapterException('No SKU data available in the SKU Group response.');
        }

        $skuGroupProductBase = $this->convertSkuGroupToProductBase(
            $remoteProductResponse->skuGroup,
            $this->mapCatalogTypeToProductType($remoteProductResponse->skuGroup->type),
            []
        );
        $skuProductBase = $this->convertSkuToProductBase($skuData, $remoteProductResponse->skuGroup);

        // Override SKU-specific fields with SKU Group data, the SKU Group holds the authoritative data for these fields.
        $skuProductBase->active = $skuGroupProductBase->active;
        $skuProductBase->altId = $skuGroupProductBase->altId;
        $skuProductBase->assets = $skuGroupProductBase->assets;
        $skuProductBase->categoryIds = $skuGroupProductBase->categoryIds;
        $skuProductBase->createdAt = $skuGroupProductBase->createdAt;
        $skuProductBase->description = $skuGroupProductBase->description;
        $skuProductBase->name = $skuGroupProductBase->name;
        $skuProductBase->options = $skuGroupProductBase->options;
        $skuProductBase->updatedAt = $skuGroupProductBase->updatedAt;

        // NOTE: It's important that our returning ProductBase object uses the sku ID so that the inventory query
        // in ProductPostMetaAggregator will function using the sku ID.

        return $skuProductBase;
    }

    /**
     * @param SkuGroup $skuGroup
     * @param string $productType
     * @param array<string> $variantSkus
     * @return ProductBase
     */
    public function convertSkuGroupToProductBase(SkuGroup $skuGroup, string $productType, array $variantSkus) : ProductBase
    {
        return new ProductBase([
            // Basic product information from SKU Group (parent level data)
            'active'                      => $this->mapCatalogStatusToActive($skuGroup->status),
            'altId'                       => $skuGroup->name, // Use SKU Group name as alternative ID
            'allowCustomPrice'            => null, // Not available
            'assets'                      => $this->convertMediaObjectsToAssets($skuGroup->getMediaObjects()),
            'brand'                       => null, // Not available
            'categoryIds'                 => $this->convertListObjectsToCategoryIds($skuGroup->lists),
            'channelIds'                  => [], // @todo MWC-18585
            'createdAt'                   => $skuGroup->createdAt,
            'description'                 => $skuGroup->htmlDescription ?: $skuGroup->description,
            'ean'                         => null, // SKU Groups don't have EAN codes
            'externalIds'                 => [],
            'files'                       => null, // v2 does not support files; setting to null ensures we won't adapt "empty files" in ProductPostMetaAdapter
            'inventory'                   => null, // SKU Groups don't have inventory (variants do)
            'manufacturerData'            => null, // Not available
            'name'                        => $skuGroup->label,
            'options'                     => $this->convertOptionsFromSkuGroup($skuGroup),
            'parentId'                    => null, // SKU Groups are parents, not children
            'price'                       => null,
            'productId'                   => $skuGroup->id,
            'purchasable'                 => true, // SKU Groups are purchasable through their variants
            'salePrice'                   => null, // SKU Groups don't have sale prices
            'shippingWeightAndDimensions' => null, // SKU Groups don't have shipping data
            'shortCode'                   => null, // Not available
            'sku'                         => $skuGroup->name, // Use SKU Group name as parent SKU
            'taxCategory'                 => ProductBase::TAX_CATEGORY_STANDARD,
            'type'                        => $productType,
            'updatedAt'                   => $skuGroup->updatedAt,
            'variantOptionMapping'        => null,
            'variants'                    => ! empty($variantSkus) ? $variantSkus : null,
        ]);
    }
}
