<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\UpdateLocalProductService as V1UpdateLocalProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * V2 version of UpdateLocalProductService.
 *
 * Extends the V1 service and overrides specific methods for V2 catalog implementation.
 */
class UpdateLocalProductService extends V1UpdateLocalProductService
{
    protected ?SkuRequestOutput $skuRequestOutput = null;
    protected ?SkuGroupRequestOutput $skuGroupRequestOutput = null;

    public function setSkuRequestOutput(SkuRequestOutput $skuRequestOutput) : void
    {
        $this->skuRequestOutput = $skuRequestOutput;
    }

    public function setSkuGroupRequestOutput(SkuGroupRequestOutput $skuGroupRequestOutput) : void
    {
        $this->skuGroupRequestOutput = $skuGroupRequestOutput;
    }

    /**
     * Set core Product properties for V2 implementation.
     *
     * This method is responsible for setting properties not supported by the Commerce Platform.
     * V2 implementation will be added here.
     *
     * @param Product $coreProduct
     * @param ProductBase $remoteProduct
     * @param WC_Product $wcProduct
     * @return Product
     */
    public function setCoreProductProperties(Product $coreProduct, ProductBase $remoteProduct, WC_Product $wcProduct) : Product
    {
        $coreProduct = parent::setCoreProductProperties($coreProduct, $remoteProduct, $wcProduct);

        $coreProduct->setStatus($this->getCoreProductStatus($wcProduct->is_type('simple'), $wcProduct->get_status()));

        /*
         * We want to preserve existing Marketplaces data from the local database. Some of this may not be supported in
         * the v2 API properly, which means when {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\UpdateLocalProductService::makeWooProduct()}
         * calls {@see ProductAdapter::convertToSource()}, that would call {@see ProductAdapter::convertMarketplacesDataToSource()}
         * which would set all blank values for Marketplaces data. But by explicitly setting it here while we have access
         * to the original WC_Product, we can ensure local database values don't get overwritten.
         * Any data that *is* directly supported by the v2 API will overwrite what we set here further down the chain.
         */
        ProductAdapter::getNewInstance($wcProduct)
            ->convertMarketplacesDataFromSource($coreProduct);

        return $coreProduct;
    }

    /**
     * Get the core product status based on the SKU or SKU Group request output.
     *
     * @param bool $isSimpleProduct
     * @param string $localStatus
     * @return string
     */
    protected function getCoreProductStatus(bool $isSimpleProduct, string $localStatus) : string
    {
        if ($isSimpleProduct) {
            // For simple products, prioritize SKU Group status over SKU status
            $remoteStatus = $this->skuRequestOutput->skuGroup->status ?? $this->skuGroupRequestOutput->skuGroup->status ??
                // if neither is set, fall back to SKU status
                $this->skuRequestOutput->sku->status ??
                null;
        } else {
            // For variable products and variations, prioritize SKU status over SKU Group status
            $remoteStatus = $this->skuRequestOutput->sku->status ??
                $this->skuGroupRequestOutput->skuGroup->status ?? $this->skuRequestOutput->skuGroup->status ??
                null;
        }

        return $this->mapStatuses($remoteStatus ?? 'DRAFT', $localStatus);
    }

    /**
     * Map remote SKU status to WooCommerce product status.
     *
     * @param string $remoteStatus
     * @param string $localStatus
     * @return string
     */
    protected function mapStatuses(string $remoteStatus, string $localStatus) : string
    {
        switch ($remoteStatus) {
            case 'ARCHIVED':
                return 'trash';
            case 'ACTIVE':
            case 'DRAFT':
            default:
                // 'private' and 'pending' have no equivalent on the platform — preserve them.
                if (in_array($localStatus, ['private', 'pending'], true)) {
                    return $localStatus;
                }

                return $remoteStatus === 'ACTIVE' ? 'publish' : 'draft';
        }
    }

    /**
     * Override to handle V2-specific metadata including backorder settings.
     *
     * @param WC_Product $product
     * @param ProductBase $remoteProduct
     * @return void
     */
    protected function saveAndMaybeSyncProduct(WC_Product $product, ProductBase $remoteProduct) : void
    {
        /*
         * Null-ify ProductBase::$type
         * We do this because we do not support reading it from the remote platform on update operations. This is because
         * it's immutable in the platform but fully editable in WooCommerce. So after it's initially set during product
         * creation, we want the WooCommerce value to always be the source of truth and never want to use the platform's value.
         * By setting it to null here we ensure it won't be adapted/updated in the local database in the ProductPostMetaAdapter.
         */
        $remoteProduct->type = null;

        // Handle V2-specific metadata before standard sync
        if ($this->skuRequestOutput !== null) {
            $this->syncV2SpecificMeta($product);
        }

        // Call parent to handle standard metadata sync
        parent::saveAndMaybeSyncProduct($product, $remoteProduct);
    }

    /**
     * Sync V2-specific metadata from SKU data to WooCommerce product.
     *
     * @param WC_Product $product
     * @return void
     */
    protected function syncV2SpecificMeta(WC_Product $product) : void
    {
        if (! $this->skuRequestOutput) {
            return;
        }

        $sku = $this->skuRequestOutput->sku;

        // Sync backorder setting
        $this->syncBackorderMeta($product, $sku->backorderLimit);

        // Sync inventory tracking setting
        $this->syncInventoryTrackingMeta($product, $sku->disableInventoryTracking);
    }

    /**
     * Sync backorder metadata from SKU backorderLimit to WooCommerce _backorders setting.
     *
     * @param WC_Product $product
     * @param int|null $backorderLimit
     * @return void
     */
    protected function syncBackorderMeta(WC_Product $product, ?int $backorderLimit) : void
    {
        // Store the original remote backorderLimit value in postmeta for reference
        if ($backorderLimit !== null) {
            $product->update_meta_data('_mwc_catalog_v2_backorder_limit', (string) $backorderLimit);
        } else {
            $product->update_meta_data('_mwc_catalog_v2_backorder_limit', 'null');
        }

        // Map backorderLimit to WooCommerce _backorders setting
        if ($backorderLimit === 0) {
            // Remote explicitly disables backorders
            $product->set_backorders('no');
        } else {
            // Remote allows backorders (null = unlimited, positive = limited)
            // Preserve existing WooCommerce setting if it's already 'yes' or 'notify'
            $currentSetting = $product->get_backorders();
            if ($currentSetting === 'no') {
                // Change from 'no' to 'yes' when remote allows backorders
                $product->set_backorders('yes');
            }
            // If current setting is 'yes' or 'notify', leave it unchanged
        }
    }

    /**
     * Sync inventory tracking metadata from SKU disableInventoryTracking to WooCommerce _manage_stock setting.
     *
     * @param WC_Product $product
     * @param bool $disableInventoryTracking
     * @return void
     */
    protected function syncInventoryTrackingMeta(WC_Product $product, bool $disableInventoryTracking) : void
    {
        // disableInventoryTracking is the inverse of manage_stock
        $product->set_manage_stock(! $disableInventoryTracking);
    }
}
