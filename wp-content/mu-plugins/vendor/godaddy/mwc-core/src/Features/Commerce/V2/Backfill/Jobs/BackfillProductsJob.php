<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Backfill\Jobs;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductsJob as V1BackfillProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\WriteProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\ProductMapRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuGroupMapRepository;
use WC_Product;

/**
 * V2-aware backfill job for products.
 */
class BackfillProductsJob extends V1BackfillProductsJob
{
    protected SkuGroupMapRepository $skuGroupMapRepository;

    /**
     * Constructor.
     *
     * @param ProductMapRepositoryContract $productMapRepository (unused in V2)
     * @param SkuGroupMapRepository $skuGroupMapRepository
     * @param SkippedProductsRepository $skippedProductsRepository
     * @param WriteProductService $writeProductService
     */
    public function __construct(
        ProductMapRepositoryContract $productMapRepository,
        SkuGroupMapRepository $skuGroupMapRepository,
        SkippedProductsRepository $skippedProductsRepository,
        WriteProductService $writeProductService
    ) {
        parent::__construct(
            $productMapRepository,
            $skippedProductsRepository,
            $writeProductService
        );

        $this->skuGroupMapRepository = $skuGroupMapRepository;
    }

    /**
     * Gets local WooCommerce products that do not yet have V2 SkuGroup mappings.
     *
     * Relevant V2 implementation notes:
     * In V2, both simple products and parent products in a variable product group are expected to have SkuGroup mappings.
     * So any missing SkuGroup mappings indicate that the product needs to be backfilled.
     *
     *
     * @return WC_Product[]|null
     * @throws WordPressDatabaseException
     */
    protected function getLocalResources() : ?array
    {
        $batchSize = $this->getJobSettings()->maxPerBatch;

        $productIds = $this->skuGroupMapRepository->getUnmappedLocalIds($batchSize);

        if (empty($productIds)) {
            return null;
        }

        // Fetch actual product objects for both simple and variable types
        /** @var WC_Product[] $products */
        $products = CatalogIntegration::withoutReads(function () use ($productIds, $batchSize) {
            return ProductsRepository::query([
                'include'        => $productIds,
                'type'           => ['simple', 'variable'],
                'posts_per_page' => $batchSize,
            ]);
        });

        $this->skipUnloadableProducts($productIds, $products);

        // Use the SQL result count (including skipped) so the batch correctly reflects
        // that work was attempted and the job should continue to the next batch.
        $this->setAttemptedResourcesCount(count($productIds));

        return $products;
    }

    /**
     * Marks products that the SQL query found but WooCommerce couldn't load as skipped.
     *
     * @param int[] $queriedIds
     * @param WC_Product[] $loadedProducts
     * @throws WordPressDatabaseException
     */
    protected function skipUnloadableProducts(array $queriedIds, array $loadedProducts) : void
    {
        $loadedIds = array_map(fn (WC_Product $product) => $product->get_id(), $loadedProducts);
        $missingIds = array_values(array_diff($queriedIds, $loadedIds));

        foreach ($missingIds as $missingId) {
            $this->markLocalResourceAsSkipped($missingId);
        }
    }
}
