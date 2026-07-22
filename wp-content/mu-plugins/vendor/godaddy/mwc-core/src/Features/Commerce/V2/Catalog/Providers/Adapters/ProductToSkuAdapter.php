<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductAttributeMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductMediaObjectsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductPricesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductTimestampsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanMapProductStatusTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanMapWeightUnitsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping\SkuGroupMappingService;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use InvalidArgumentException;

/**
 * Adapter to convert Product model to SKU data object.
 *
 * Maps Product model properties to SKU fields:
 *  - code (SKU), label, name
 *  - status (based on product status)
 *  - weight, unitOfWeight
 *  - disableShipping (based on virtual/downloadable status)
 *  - skuGroupId reference (determined internally)
 *
 * The following API properties do not have direct equivalents in WooCommerce and are not mapped:
 * - shortLabel
 * - disablePriceOverrides
 * - isbnCode
 * - eanCode
 */
class ProductToSkuAdapter
{
    use CanConvertProductMediaObjectsTrait;
    use CanConvertProductPricesTrait;
    use CanConvertProductTimestampsTrait;
    use CanMapProductStatusTrait;
    use CanMapWeightUnitsTrait;

    /** @var SkuGroupMappingService */
    protected SkuGroupMappingService $skuGroupMappingService;

    public function __construct(
        SkuGroupMappingService $skuGroupMappingService
    ) {
        $this->skuGroupMappingService = $skuGroupMappingService;
    }

    /**
     * Convert a core WooCommerce {@see Product} model to a {@see Sku} data object.
     *
     * @param Product $product
     * @param string|null $remoteId Existing remote SKU ID if updating; null if creating
     * @return Sku
     * @throws AdapterException
     */
    public function convert(Product $product, ?string $remoteId = null) : Sku
    {
        try {
            $data = [
                // Basic identification
                'name'                     => $product->getSlug(),
                'label'                    => $product->getName(),
                'code'                     => $product->getSku() ?: '', // Product SKU code
                'skuGroupId'               => $this->determineSkuGroupId($product),
                'description'              => wp_strip_all_tags($product->getDescription()),
                'htmlDescription'          => $product->getDescription(),
                'status'                   => $this->mapProductStatus($product->getStatus()),
                'upcCode'                  => $product->getGlobalUniqueId(),
                'gtinCode'                 => $product->getMarketplacesGtin(),
                'weight'                   => $this->getWeightValue($product),
                'unitOfWeight'             => $this->getWeightUnit($product),
                'disableShipping'          => $product->isVirtual() || $product->isDownloadable(),
                'disableInventoryTracking' => $this->getDisableInventoryTracking($product),
                'backorderLimit'           => $this->getBackorderLimit($product),
                'createdAt'                => $this->formatDateTimeForApi($product->getCreatedAt()),
                'updatedAt'                => $this->formatDateTimeForApi($product->getUpdatedAt()),
                'archivedAt'               => $this->getArchivedAt($product),
                'prices'                   => $this->convertProductPrices($product->getRegularPrice(), $product->getSalePrice()),
                'mediaObjects'             => $this->convertProductMediaObjects($product),
                'attributeValues'          => $this->convertProductAttributeValues($product),
                'metafields'               => MetafieldsAdapter::getNewInstance($product)->convertFromSource(),
            ];

            if ($remoteId) {
                $data['id'] = $remoteId;
            }

            return Sku::getNewInstance($data);
        } catch (AdapterException $e) {
            throw $e;
        } catch (Exception $e) {
            // rethrow as AdapterException for consistency
            throw new AdapterException('Failed to convert Product ID '.$product->getId().' to SKU: '.$e->getMessage(), $e);
        }
    }

    /**
     * Determine the SKU Group ID for the given product.
     *
     * - For simple products, use their own mapping.
     * - For variations, use the mapping of their parent product.
     * - No other product types should end up in here.
     *
     * @param Product $product
     * @return string remote SKU Group UUID
     * @throws AdapterException if SKU Group ID cannot be determined
     */
    protected function determineSkuGroupId(Product $product) : string
    {
        switch($product->getType()) {
            /*
             * Simple products have their own SKU Group. ID is linked to the product itself.
             */
            case 'simple':
                $skuGroupId = $this->skuGroupMappingService->getRemoteId($product);
                if (! $skuGroupId) {
                    throw new AdapterException('SKU Group ID not found for simple product ID '.$product->getId());
                }

                return $skuGroupId;
                /*
                 * Variations use the SKU Group of their parent product. So we first need to look up the parent, then
                 * check the mapping table for the SKU group associated with that parent ID.
                 */
            case 'variation':
                $parentId = $product->getParentId();
                if (! $parentId) {
                    throw new AdapterException('Parent product not found for variation ID '.$product->getId());
                }

                $skuGroupId = $this->skuGroupMappingService->getRemoteId((new Product())->setId($parentId));
                if (! $skuGroupId) {
                    throw new AdapterException('SKU Group ID not found for parent product ID '.$parentId);
                }

                return $skuGroupId;
            default:
                // note: variable products are intentionally not here because they don't have SKUs, so it's unexpected that one would end up here
                throw new AdapterException('Unsupported product type: '.$product->getType());
        }
    }

    /**
     * Get weight value from Product.
     *
     * @param Product $product
     * @return float|null
     */
    protected function getWeightValue(Product $product) : ?float
    {
        $weight = $product->getWeight();

        return $weight ? $weight->getValue() : null;
    }

    /**
     * Get weight unit from Product and map to SKU format.
     *
     * @param Product $product
     * @return string|null
     */
    protected function getWeightUnit(Product $product) : ?string
    {
        $weight = $product->getWeight();

        // No weight object or no weight value = no unit needed
        if (! $weight || ! $weight->getValue()) {
            return null;
        }

        $wooUnit = $weight->getUnitOfMeasurement();
        if (! $wooUnit) {
            return null;
        }

        // Has weight value, map the unit (with fallback if unit not set)
        return $this->mapWeightUnitToSku($wooUnit);
    }

    /**
     * Get whether inventory tracking is disabled.
     *
     * @param Product $product
     * @return bool
     */
    protected function getDisableInventoryTracking(Product $product) : bool
    {
        // Return opposite of WooCommerce's manage stock setting
        return ! $product->getStockManagementEnabled();
    }

    /**
     * Map WooCommerce backorder setting to API backorderLimit value.
     *
     * Returns the appropriate backorder limit for the Commerce API based on product settings:
     * - 0: Backorders are disabled (WooCommerce backorders = 'no')
     * - null: Unlimited backorders (WooCommerce backorders = 'yes' or 'notify', no specific limit)
     * - positive integer: Specific backorder limit (WooCommerce backorders enabled with a stored limit)
     *
     * The _mwc_catalog_v2_backorder_limit meta is used to preserve backorder limits set via
     * the Commerce API. The platform supports as positive integer limit, while WooCommerce has no
     * equivalent mechanism for limiting backorders to a specific quantity. So when the API limit is set
     * we need to references it when we write it back to the API during a Woo product update.
     *
     * @param Product $product
     * @return int|null 0 if backorders disabled, positive integer for specific limit, null for unlimited
     * @throws InvalidArgumentException if the WooCommerce product cannot be found
     */
    protected function getBackorderLimit(Product $product) : ?int
    {
        // No backorders allowed
        if ('no' === $product->getBackordersAllowed()) {
            return 0;
        }

        // Backorders are allowed ('yes' or 'notify')
        $wcProduct = ProductsRepository::get(TypeHelper::int($product->getId(), 0));

        if (! $wcProduct) {
            throw new InvalidArgumentException("WooCommerce product not found for ID: {$product->getId()}");
        }

        // Check for a stored backorder limit (only positive values are valid limits)
        $storedBackorderLimit = $wcProduct->get_meta('_mwc_catalog_v2_backorder_limit');
        $limitValue = TypeHelper::int($storedBackorderLimit, 0);

        // If a positive limit is stored, return it; otherwise unlimited (null)
        return $limitValue > 0 ? $limitValue : null;
    }

    /**
     * Convert Product variation attributes to GraphQL SKU attributeValues format.
     *
     * @param Product $product
     * @return array<string, mixed>[]
     */
    protected function convertProductAttributeValues(Product $product) : array
    {
        return AttributeValuesAdapter::getNewInstance($product)
            ->setProductAttributeMappingService(ProductAttributeMappingService::for($product))
            ->convertFromSource();
    }
}
