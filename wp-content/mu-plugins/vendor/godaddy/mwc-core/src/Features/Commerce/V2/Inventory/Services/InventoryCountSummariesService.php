<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListLevelsByRemoteIdOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;

/**
 * Inventory summaries service for V2.
 * Extends the v1 summaries service {@see SummariesService}
 * to inherit caching behavior while using v2 API integration.
 *
 * The v2 API does not have a separate "summaries" resource; instead summaries are derived from inventory counts.
 * This service wraps the v2 {@see InventoryCountsService} to extract Summary objects from Level objects.
 */
class InventoryCountSummariesService extends SummariesService
{
    protected InventoryCountsService $inventoryCountsService;
    protected LocationMappingService $locationMappingService;

    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        InventoryCountsService $inventoryCountsService,
        SummariesCachingService $summariesCachingService,
        SkuMapRepository $skuMapRepository,
        LocationMappingService $locationMappingService
    ) {
        parent::__construct(
            $commerceContext,
            $provider,
            $summariesCachingService,
            $skuMapRepository
        );

        $this->inventoryCountsService = $inventoryCountsService;
        $this->locationMappingService = $locationMappingService;
    }

    // readSummary(), list(), listSummariesWithCache(), and withoutCache() methods
    // are inherited from parent SummariesService and work identically

    /**
     * {@inheritDoc}
     *
     * V2 implementation: Lists summaries by extracting Summary objects from Level objects
     * returned by InventoryCountsService instead of calling the provider gateway directly.
     */
    protected function listSummariesFromRemoteService(array $productIds) : array
    {
        // Create operation for the InventoryCountsService
        // In v2, productIds are actually SKU IDs (remote product identifiers)
        $listLevelsOperation = ListLevelsByRemoteIdOperation::seed([
            'ids' => $productIds,
        ]);

        // Get levels from InventoryCountsService
        $listLevelsResponse = $this->inventoryCountsService->listLevelsByRemoteProductId($listLevelsOperation);
        $levels = $listLevelsResponse->getLevels();

        // Extract summaries from levels
        $foundSummaries = [];
        foreach ($levels as $level) {
            if ($level->summary instanceof Summary) {
                $foundSummaries[] = $level->summary;
            }
        }

        // Mark any productIds that didn't return summary data as skipped for future queries
        $this->summariesCachingService->addSkippedResourceIds($this->getUncachedSummariesProductIds($productIds, $foundSummaries));

        return $foundSummaries;
    }
}
