<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\BatchRequestHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\PrimeInventoryCacheServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListLevelsByRemoteIdOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\ProductMapRepositoryContract;

/**
 * Service for priming inventory caches.
 *
 * This service is called from multiple entry points:
 * - ProductsListedPrimeCacheSubscriber (ProductsListedEvent)
 *
 * It batch-fetches inventory summaries and levels to populate the cache,
 * preventing N+1 API call problems when products are rendered.
 */
class PrimeInventoryCacheService implements PrimeInventoryCacheServiceContract
{
    protected ProductMapRepositoryContract $productMapRepository;
    protected SummariesServiceContract $summariesService;
    protected LevelsServiceWithCacheContract $levelsService;

    public function __construct(
        ProductMapRepositoryContract $productMapRepository,
        SummariesServiceContract $summariesService,
        LevelsServiceWithCacheContract $levelsService
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->summariesService = $summariesService;
        $this->levelsService = $levelsService;
    }

    /**
     * {@inheritDoc}
     */
    public function primeByLocalProductIds(array $localProductIds) : void
    {
        if (empty($localProductIds)) {
            return;
        }

        $remoteProductIds = $this->convertLocalIdsToRemoteIds($localProductIds);

        if (empty($remoteProductIds)) {
            return;
        }

        $this->primeByRemoteProductIds($remoteProductIds);
    }

    /**
     * {@inheritDoc}
     */
    public function primeByRemoteProductIds(array $remoteProductIds) : void
    {
        foreach (array_chunk($remoteProductIds, BatchRequestHelper::getMaxIdsPerRequest()) as $ids) {
            $this->primeBatchOfRemoteProductIds($ids);
        }
    }

    /**
     * @param string[] $remoteProductIds
     */
    protected function primeBatchOfRemoteProductIds(array $remoteProductIds) : void
    {
        try {
            $this->summariesService->list(ListSummariesOperation::seed([
                'productIds' => $remoteProductIds,
            ]));

            $this->levelsService->listLevelsByRemoteProductId(ListLevelsByRemoteIdOperation::seed([
                'ids' => $remoteProductIds,
            ]));
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance("Could not prime inventory caches: {$exception->getMessage()}", $exception);
        }
    }

    /**
     * Converts local product IDs to remote product IDs.
     *
     * @param int[] $localProductIds
     * @return string[]
     */
    protected function convertLocalIdsToRemoteIds(array $localProductIds) : array
    {
        $mappings = $this->productMapRepository->getMappingsByLocalIds($localProductIds);

        return array_values(array_filter($mappings->getRemoteIds()));
    }
}
