<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\CreateOrUpdateProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WC_Product;
use WC_Product_Variable;

/**
 * Handler to respond to a trashed product being "untrashed".
 * When we do this we need to update the product in the platform to change {@see ProductBase::$active} to `true`.
 */
class ProductUntrashedHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productsMappingService;

    /**
     * Constructor.
     */
    public function __construct(ProductsServiceContract $productsService, ProductsMappingServiceContract $productsMappingService)
    {
        $this->productsService = $productsService;
        $this->productsMappingService = $productsMappingService;
    }

    /**
     * Handler runs on the `untrashed_post` action.
     *
     * This hook fires for all post types, so first we have to validate if the provided post id is a valid product.
     * For variable products on the v2 catalog, the platform-side cascade-archive performed when the parent
     * was trashed (see {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers\VariableProductHandler})
     * archives every variant SKU alongside the SKU Group. There is no inverse cascade mutation on the platform,
     * so on un-trash we iterate variations client-side and push each one to restore its natural status.
     * The v1 catalog has no equivalent cascade, so the per-variation restore loop is skipped there.
     * {@link https://developer.wordpress.org/reference/hooks/untrashed_post/}
     */
    public function run(...$args)
    {
        /** @var int $postId */
        $postId = $args[0] ?? null;

        /** @var WC_Product|null $sourceProduct */
        $sourceProduct = ProductsRepository::get($postId);

        if (! $sourceProduct) {
            return;
        }

        try {
            $this->pushProductUpdate($sourceProduct, true);

            if ($sourceProduct instanceof WC_Product_Variable && CatalogIntegration::shouldUseV2Api()) {
                $this->restoreVariations($sourceProduct);
            }
        } catch (Exception $e) {
            SentryException::getNewInstance('Failed to handle untrashed product: '.$e->getMessage(), $e);
        }
    }

    /**
     * Pushes each child variation back to the platform after the parent has been un-trashed.
     *
     * Variant SKUs were archived alongside the parent SkuGroup via `cascadeArchive` on the way down,
     * but the platform has no inverse mutation, so each variation must be pushed individually.
     * A failure for one variation should not stop the others from being restored, so per-variation
     * exceptions are logged and the loop continues.
     */
    protected function restoreVariations(WC_Product_Variable $parent) : void
    {
        foreach (TypeHelper::arrayOfIntegers($parent->get_children()) as $variationId) {
            try {
                /** @var WC_Product|null $variation */
                $variation = ProductsRepository::get($variationId);

                if (! $variation) {
                    continue;
                }

                $this->pushProductUpdate($variation, false);
            } catch (Exception $e) {
                SentryException::getNewInstance('Failed to restore untrashed variation '.$variationId.': '.$e->getMessage(), $e);
            }
        }
    }

    /**
     * Pushes the current local state of a single product to the Commerce platform.
     *
     * @param bool $forceStatusPublish When true, override the converted product's status to `'publish'` before
     *                                 pushing. Used for the parent post on the way out of trash, where the post
     *                                 status may still report `'trash'` at hook time. Variations are not status-overridden
     *                                 because their post status is untouched by parent trash/untrash.
     *
     * @throws Exception
     * @throws GatewayRequestException
     */
    protected function pushProductUpdate(WC_Product $sourceProduct, bool $forceStatusPublish) : void
    {
        $nativeProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();

        if ($forceStatusPublish) {
            $nativeProduct->setStatus('publish');
        }

        $remoteId = $this->productsMappingService->getRemoteId($nativeProduct);

        // @NOTE if we don't have a remote ID that means the product hasn't been written to the platform yet
        if (! $remoteId) {
            return;
        }

        $operation = CreateOrUpdateProductOperation::fromProduct($nativeProduct);
        $this->productsService->updateProduct($operation, $remoteId);
    }
}
