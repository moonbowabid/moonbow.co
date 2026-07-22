<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\AssetReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CategoryReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CategoryWritesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CommerceProductUuidRequestInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CostOfGoodsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CreateOrUpdateRemoteVariantsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CrossSellProductsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\DeleteProductDeletedUpstreamJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ListRemoteVariantsJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\LocalAttachmentDeletedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\LocalCategoryDeletedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\LocalProductDeletedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\PrimePostCachesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductCategoryDeleteInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductEditInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductTrashedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductUntrashedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductVariationDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\RelatedProductsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\RemoteAssetDownloadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\SaveLocalProductAfterRemoteUpdateInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\UpdateProductMetaCacheInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\VariableProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\WooProductImportInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\WpQueryInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\CommerceCatalogV2Mapping;

/**
 * Commerce Catalog integration class.
 */
class CatalogIntegration extends AbstractIntegration
{
    use IntegrationEnabledOnTestTrait;

    /** @var string the name of the integration */
    public const NAME = 'catalog';

    /** @var string Product category taxonomy name */
    public const PRODUCT_CATEGORY_TAXONOMY = 'product_cat';

    /** @var string WooCommerce product post type name */
    public const PRODUCT_POST_TYPE = 'product';

    /** @var string WooCommerce product variation post type name */
    public const PRODUCT_VARIATION_POST_TYPE = 'product_variation';

    /** @var string Uncategorized category is ineligible for writing to commerce */
    public const INELIGIBLE_PRODUCT_CATEGORY_NAME = 'uncategorized';

    /** @var class-string[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        AssetReadInterceptor::class,
        CategoryReadInterceptor::class,
        CategoryWritesInterceptor::class,
        CreateOrUpdateRemoteVariantsInterceptor::class,
        LocalCategoryDeletedInterceptor::class,
        CrossSellProductsInterceptor::class,
        LocalAttachmentDeletedInterceptor::class,
        LocalProductDeletedInterceptor::class,
        ProductReadInterceptor::class,
        UpdateProductMetaCacheInterceptor::class,
        ProductDataStoreInterceptor::class,
        SaveLocalProductAfterRemoteUpdateInterceptor::class,
        ProductVariationDataStoreInterceptor::class,
        VariableProductDataStoreInterceptor::class,
        PrimePostCachesInterceptor::class,
        WpQueryInterceptor::class,
        CostOfGoodsInterceptor::class,
        ProductEditInterceptor::class,
        ListRemoteVariantsJobInterceptor::class,
        ProductCategoryDeleteInterceptor::class,
        ProductTrashedInterceptor::class,
        ProductUntrashedInterceptor::class,
        CommerceProductUuidRequestInterceptor::class,
        RelatedProductsInterceptor::class,
        RemoteAssetDownloadInterceptor::class,
        WooProductImportInterceptor::class,
        DeleteProductDeletedUpstreamJobInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getIntegrationName() : string
    {
        return self::NAME;
    }

    /**
     * Returns true if the catalog integration should use the v2 API instead of v1.
     *
     * @return bool
     */
    public static function shouldUseV2Api() : bool
    {
        if (defined('MWC_COMMERCE_CATALOG_USE_V2_API')) {
            return (bool) MWC_COMMERCE_CATALOG_USE_V2_API;
        }

        return CommerceCatalogV2Mapping::hasCompletedMappingJobs();
    }

    /**
     * Gets the webhook event types for this integration based on the current API version.
     *
     * @return array<string, string>
     */
    public static function getWebhookEventTypes() : array
    {
        $config = static::getConfiguration('webhooks.eventTypes', []);

        $version = static::shouldUseV2Api() ? 'v2' : 'v1';

        /** @var array<string, string> $events */
        $events = TypeHelper::array(ArrayHelper::get($config, $version, []), []);

        return array_filter($events, 'is_string');
    }

    /**
     * Override the commerce capabilities to disable read when using the v2 API.
     *
     * @return array|bool[]
     */
    public static function getCommerceCapabilities() : array
    {
        $capabilities = parent::getCommerceCapabilities();

        if (static::shouldUseV2Api()) {
            // The v2 integration uses webhooks and does not read headlessly.
            $capabilities[Commerce::CAPABILITY_READ] = false;
        }

        return $capabilities;
    }
}
