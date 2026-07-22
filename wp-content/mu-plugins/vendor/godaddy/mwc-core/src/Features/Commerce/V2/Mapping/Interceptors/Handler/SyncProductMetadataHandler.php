<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors\Handler;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaSynchronizer;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Exceptions\SyncProductMetadataFailedException;
use Throwable;
use WC_Product;

class SyncProductMetadataHandler extends AbstractInterceptorHandler
{
    /** @var int[] */
    protected static array $alreadyListedLocalIds = [];

    protected ProductsServiceContract $productsService;
    protected ProductMapRepository $productMapRepository;
    protected ProductPostMetaSynchronizer $productPostMetaSynchronizer;

    public function __construct(
        ProductsServiceContract $productsService,
        ProductMapRepository $productMapRepository,
        ProductPostMetaSynchronizer $productPostMetaSynchronizer
    ) {
        $this->productsService = $productsService;
        $this->productMapRepository = $productMapRepository;
        $this->productPostMetaSynchronizer = $productPostMetaSynchronizer;
    }

    /**
     * @throws SyncProductMetadataFailedException
     */
    public function run(...$args) : void
    {
        $localIds = TypeHelper::arrayOfIntegers(ArrayHelper::get($args, 0));
        $nextLocalId = TypeHelper::int(ArrayHelper::get($args, 1), 0);

        if (empty($localIds) || $nextLocalId <= 0) {
            throw new SyncProductMetadataFailedException('Could not sync product metadata because one of the parameters is not valid.');
        }

        try {
            $this->tryToSyncProductMetadata($localIds, $nextLocalId);
        } catch (Throwable $throwable) {
            throw new SyncProductMetadataFailedException(
                "An error occurred trying to sync product metadata for product with local ID {$nextLocalId}: {$throwable->getMessage()}",
                $throwable
            );
        }
    }

    /**
     * @param non-empty-array<int> $localIds
     * @param positive-int $nextLocalId
     * @throws BaseException
     * @throws CommerceExceptionContract
     */
    protected function tryToSyncProductMetadata(array $localIds, int $nextLocalId) : void
    {
        $this->maybePrimeProductsCache($localIds);

        $product = $this->productsService->readProduct(ReadProductOperation::seed(['localId' => $nextLocalId]))->getProduct();

        $this->syncProductMetadata($product, $nextLocalId);
    }

    /**
     * @param non-empty-array<int> $localIds
     * @throws BaseException
     * @throws CommerceExceptionContract
     */
    protected function maybePrimeProductsCache(array $localIds) : void
    {
        // Do not list products if this process already attempted to list products with the given local IDs.
        //
        // We expect Action Scheduler to run multiple instances of this handler on a single process, many of them
        // with the same list of local IDs but a different next local ID. We want the first instance to list all
        // products so that the following ones can read the product information from cache.
        //
        // We are aware that listing products already retrieves information from cache. However, every time we
        // list products, a {@see ProductsListedEvent} is broadcast, and when that happens the Inventory integration
        // sends uncached requests to retrieve inventory summaries and inventory levels.
        if (count(array_diff($localIds, self::$alreadyListedLocalIds)) === 0) {
            return;
        }

        $this->productsService->listProductsByLocalIds($localIds)->getProducts();

        self::$alreadyListedLocalIds = array_unique(array_merge(self::$alreadyListedLocalIds, $localIds));
    }

    /**
     * Syncs meta fields for a single product using V1 ProductBase data.
     *
     * @param positive-int $localId
     */
    protected function syncProductMetadata(
        ProductBase $productBase,
        int $localId
    ) : void {
        $product = CatalogIntegration::withoutReads(fn () => wc_get_product($localId));

        if (! $product instanceof WC_Product || 'trash' === $product->get_status()) {
            return;
        }

        $this->productPostMetaSynchronizer->syncProductMeta($product, $productBase);

        // Clear the cache here to force WooCommerce to load everything from the database the next
        // time the product is instantiated.
        wp_cache_delete($localId, 'post_meta');
    }
}
