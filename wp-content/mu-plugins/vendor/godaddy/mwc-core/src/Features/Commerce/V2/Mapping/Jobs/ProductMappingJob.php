<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Jobs;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaSynchronizer;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\AbstractSkippedResourcesRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\ProductReferences;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Exceptions\SyncMetadataJobSchedulingFailedException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors\SyncProductMetadataInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkippedSkuGroupMappingRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkippedSkuMappingRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuGroupMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Services\Contracts\SkuReferencesServiceContract;

/**
 * Product mapping job class.
 *
 * This job maps local WooCommerce products to V2 Commerce API UUIDs.
 */
class ProductMappingJob extends AbstractMappingJob
{
    /** @var string unique identifier for the queue.jobs config */
    public const JOB_KEY = 'v2ProductMapping';

    /** @var SkuGroupMapRepository for inserting new v2 records */
    protected SkuGroupMapRepository $skuGroupMapRepository;

    /** @var SkuMapRepository for inserting new v2 records */
    protected SkuMapRepository $skuMapRepository;

    /** @var ProductMapRepository for querying old v1 records */
    protected ProductMapRepository $v1ResourceRepository;

    /** @var ProductPostMetaSynchronizer for syncing meta fields */
    protected ProductPostMetaSynchronizer $productPostMetaSynchronizer;

    /** @var ProductsServiceContract V1 products service for fetching complete product data */
    protected ProductsServiceContract $v1ProductsService;

    /** @var SkippedSkuGroupMappingRepository for tracking products that could not be mapped to a SkuGroup */
    protected SkippedSkuGroupMappingRepository $skippedSkuGroupRepository;

    /** @var SkippedSkuMappingRepository for tracking products that could not be mapped to a Sku */
    protected SkippedSkuMappingRepository $skippedSkuRepository;

    public function __construct(
        SkuGroupMapRepository $skuGroupMapRepository,
        SkuMapRepository $skuMapRepository,
        ProductMapRepository $productMapRepository,
        SkuReferencesServiceContract $skuReferencesService,
        ProductPostMetaSynchronizer $productPostMetaSynchronizer,
        ProductsServiceContract $v1ProductsService,
        SkippedSkuGroupMappingRepository $skippedSkuGroupRepository,
        SkippedSkuMappingRepository $skippedSkuRepository
    ) {
        $this->skuGroupMapRepository = $skuGroupMapRepository;
        $this->skuMapRepository = $skuMapRepository;
        $this->v1ResourceRepository = $productMapRepository;
        $this->referencesService = $skuReferencesService;
        $this->productPostMetaSynchronizer = $productPostMetaSynchronizer;
        $this->v1ProductsService = $v1ProductsService;
        $this->skippedSkuGroupRepository = $skippedSkuGroupRepository;
        $this->skippedSkuRepository = $skippedSkuRepository;

        $this->setJobSettings($this->configureJobSettings());
    }

    /** {@inheritDoc} */
    protected function getLocalResourceMapsByLocalIds(array $localIds) : ResourceMapCollection
    {
        return $this->v1ResourceRepository->getMappingsByLocalIds($localIds);
    }

    /**
     * Gets the SQL string for the unmapped local IDs query.
     *
     * We specifically base our query on the mapping table rather than the wp_posts table to ensure we only pick up
     * products that have been mapped to the v1 resource type. We don't want to return products that have never
     * been mapped to the v1 resource type, as those products will not require a v2 mapping.
     *
     * See example below for a resulting string.
     *
     * @return string
     */
    protected function getUnmappedLocalIdsSqlString() : string
    {
        $db = DatabaseRepository::instance();

        $skuGroupResourceTypeId = $this->skuGroupMapRepository->getResourceTypeId();
        $mappedSkuGroupIds = TypeHelper::string($db->prepare(
            /* @phpstan-ignore argument.type (the only reason it's not a literal string is that we use constants to reference table/column names) */
            $this->skuGroupMapRepository->getMappedLocalIdsForResourceTypeQuery(),
            $skuGroupResourceTypeId
        ), '');

        $skuResourceTypeId = $this->skuMapRepository->getResourceTypeId();
        $mappedSkuIds = TypeHelper::string($db->prepare(
            /* @phpstan-ignore argument.type (the only reason it's not a literal string is that we use constants to reference table/column names) */
            $this->skuMapRepository->getMappedLocalIdsForResourceTypeQuery(),
            $skuResourceTypeId
        ), '');

        $skippedSkuGroupIds = TypeHelper::string($db->prepare(
            /* @phpstan-ignore argument.type (the only reason it's not a literal string is that we use constants to reference table/column names) */
            SkippedSkuGroupMappingRepository::getSkippedResourcesIdsQuery(),
            $skuGroupResourceTypeId
        ), '');

        $skippedSkuIds = TypeHelper::string($db->prepare(
            /* @phpstan-ignore argument.type (the only reason it's not a literal string is that we use constants to reference table/column names) */
            SkippedSkuMappingRepository::getSkippedResourcesIdsQuery(),
            $skuResourceTypeId
        ), '');

        $productsResourceTypeId = $this->v1ResourceRepository->getResourceTypeId();
        $resourceMapsTable = CommerceTables::ResourceMap;
        $postsTable = $db->posts;

        // Example:
        // SELECT godaddy_mwc_commerce_map_ids.local_id
        // FROM godaddy_mwc_commerce_map_ids
        // INNER JOIN wp_posts ON godaddy_mwc_commerce_map_ids.local_id = wp_posts.ID
        // WHERE godaddy_mwc_commerce_map_ids.resource_type_id = 1
        //     AND wp_posts.post_type IN ('product', 'product_variation')
        //     AND godaddy_mwc_commerce_map_ids.local_id NOT IN (SELECT local_id FROM godaddy_mwc_commerce_map_ids WHERE resource_type_id = 11)
        //     AND godaddy_mwc_commerce_map_ids.local_id NOT IN (SELECT local_id FROM godaddy_mwc_commerce_map_ids WHERE resource_type_id = 12)
        //     AND godaddy_mwc_commerce_map_ids.local_id NOT IN (SELECT local_id FROM godaddy_mwc_commerce_skipped_resources WHERE resource_type_id = 11)
        //     AND godaddy_mwc_commerce_map_ids.local_id NOT IN (SELECT local_id FROM godaddy_mwc_commerce_skipped_resources WHERE resource_type_id = 12)
        // LIMIT 50
        return "
        SELECT {$resourceMapsTable}.local_id
        FROM {$resourceMapsTable}
        INNER JOIN {$postsTable} ON {$resourceMapsTable}.local_id = {$postsTable}.ID
        WHERE {$resourceMapsTable}.resource_type_id = {$productsResourceTypeId}
            AND {$postsTable}.post_type IN ('product', 'product_variation')
            AND {$resourceMapsTable}.local_id NOT IN ({$mappedSkuGroupIds})
            AND {$resourceMapsTable}.local_id NOT IN ({$mappedSkuIds})
            AND {$resourceMapsTable}.local_id NOT IN ({$skippedSkuGroupIds})
            AND {$resourceMapsTable}.local_id NOT IN ({$skippedSkuIds})
        LIMIT %d
        ";
    }

    /**
     * {@inheritDoc}
     */
    protected function addLocalMappingRecord(ResourceMap $referenceMap) : void
    {
        if ($referenceMap->resourceType === CommerceResourceTypes::SkuGroup) {
            $this->skuGroupMapRepository->add($referenceMap->localId, $referenceMap->commerceId);
        } elseif ($referenceMap->resourceType === CommerceResourceTypes::Sku) {
            $this->skuMapRepository->add($referenceMap->localId, $referenceMap->commerceId);
        }
    }

    /**
     * {@inheritDoc}
     * @param ProductReferences[] $references
     */
    protected function buildReferenceMap(array $references, ResourceMapCollection $resourceMapCollection) : array
    {
        $resourceMaps = [];
        $skuGroupMappedIds = [];
        $skuMappedIds = [];

        foreach ($references as $productReference) {
            $skuGroupMapping = $this->findAndBuildSkuGroupResourceMap($productReference, $resourceMapCollection);
            if ($skuGroupMapping) {
                $resourceMaps[] = $skuGroupMapping;
                $skuGroupMappedIds[$skuGroupMapping->localId] = true;
            }

            $skuMapping = $this->findAndBuildSkuResourceMap($productReference, $resourceMapCollection);
            if ($skuMapping) {
                $resourceMaps[] = $skuMapping;
                $skuMappedIds[$skuMapping->localId] = true;
            }
        }

        $this->skipUnmappableResources($resourceMapCollection, $skuGroupMappedIds, $skuMappedIds);

        /* @var ResourceMap[] $resourceMaps */
        return $resourceMaps;
    }

    /**
     * Attempts to find and build the SKU group resource map for the given product references.
     */
    protected function findAndBuildSkuGroupResourceMap(ProductReferences $productReferences, ResourceMapCollection $resourceMapCollection) : ?ResourceMap
    {
        return $this->findAndBuildResourceMap(
            $productReferences->skuGroupReferences,
            $resourceMapCollection,
            $productReferences->skuGroupId,
            CommerceResourceTypes::SkuGroup
        );
    }

    /**
     * Attempts to find and build the SKU resource map for the given product references.
     */
    protected function findAndBuildSkuResourceMap(ProductReferences $productReferences, ResourceMapCollection $resourceMapCollection) : ?ResourceMap
    {
        return $this->findAndBuildResourceMap(
            $productReferences->skuReferences,
            $resourceMapCollection,
            $productReferences->skuId,
            CommerceResourceTypes::Sku
        );
    }

    /**
     * Builds a {@see ResourceMap}, given the provided references and resource type.
     *
     * @param Reference[] $references
     * @param ResourceMapCollection $resourceMapCollection
     * @param string $commerceId
     * @param string $resourceType
     * @return ResourceMap|null
     */
    protected function findAndBuildResourceMap(
        array $references,
        ResourceMapCollection $resourceMapCollection,
        string $commerceId,
        string $resourceType
    ) : ?ResourceMap {
        $v1Id = $this->getV1Reference($references);
        if (! $v1Id) {
            return null;
        }

        // Fall back to V1 mapping table when the product isn't in the current batch.
        // This handles parent products whose SkuGroup data arrives via a variation's Sku response.
        $localId = $resourceMapCollection->getLocalId($v1Id) ?? $this->v1ResourceRepository->getLocalId($v1Id);
        if (! $localId) {
            return null;
        }

        return new ResourceMap([
            'commerceId'   => $commerceId,
            'localId'      => $localId,
            'resourceType' => $resourceType,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapV1ResourcesToV2(ResourceMapCollection $resourceMapCollection) : void
    {
        // First, create the UUID mappings (call parent implementation)
        parent::mapV1ResourcesToV2($resourceMapCollection);

        // Then, sync meta fields for the mapped products
        $this->scheduleJobsToSyncProductMetadata($resourceMapCollection);
    }

    protected function scheduleJobsToSyncProductMetadata(ResourceMapCollection $resourceMapCollection) : void
    {
        // Each {@see SyncProductMetadataInterceptor} job will list products for the given chunk
        // of local IDs if no other instance of the job has listed that chunk in the current process.
        //
        // Splitting the array of local IDs in chunks of 20 IDs reduces the chances of exceeding Action Scheduler
        // 191 characters limit for the arguments of the job. The jobs should still be able to list products in batches
        // and retrieve product information from cache to avoid adding more than a few requests to each process.
        foreach (array_chunk($resourceMapCollection->getLocalIds(), 20) as $localIds) {
            foreach ($localIds as $localId) {
                try {
                    Schedule::singleAction()
                        ->setName(SyncProductMetadataInterceptor::JOB_NAME)
                        ->setArguments($localIds, $localId)
                        ->setScheduleAt(new DateTime())
                        ->schedule();
                } catch (InvalidScheduleException $exception) {
                    SyncMetadataJobSchedulingFailedException::getNewInstance(
                        "Failed to schedule a job to sync product metadata for product with local ID {$localId}: {$exception->getMessage()}",
                        $exception
                    );
                }
            }
        }
    }

    /**
     * Identifies products that failed to map for each resource type and marks them as skipped.
     *
     * @param ResourceMapCollection $resourceMapCollection
     * @param array<int, true> $skuGroupMappedIds
     * @param array<int, true> $skuMappedIds
     * @return void
     */
    protected function skipUnmappableResources(ResourceMapCollection $resourceMapCollection, array $skuGroupMappedIds, array $skuMappedIds) : void
    {
        foreach ($resourceMapCollection->getLocalIds() as $localId) {
            $needsSkuGroupSkip = ! isset($skuGroupMappedIds[$localId]);
            $needsSkuSkip = ! isset($skuMappedIds[$localId]);

            if (! $needsSkuGroupSkip && ! $needsSkuSkip) {
                continue;
            }

            $remoteId = $resourceMapCollection->getRemoteId($localId) ?? 'unknown';

            if ($needsSkuGroupSkip) {
                // Don't log SkuGroup skips — parent products are expected to fail here
                // and will be resolved when a variation's batch provides their SkuGroup data.
                $this->markResourceAsSkipped($localId, $remoteId, $this->skippedSkuGroupRepository, CommerceResourceTypes::SkuGroup, false);
            }

            if ($needsSkuSkip) {
                $this->markResourceAsSkipped($localId, $remoteId, $this->skippedSkuRepository, CommerceResourceTypes::Sku);
            }
        }
    }

    /**
     * Marks a resource as skipped and logs the failure.
     *
     * @param int $localId
     * @param string $v1RemoteId
     * @param SkippedSkuGroupMappingRepository|SkippedSkuMappingRepository $repository
     * @param string $resourceType
     * @return void
     */
    protected function markResourceAsSkipped(int $localId, string $v1RemoteId, AbstractSkippedResourcesRepository $repository, string $resourceType, bool $shouldLog = true) : void
    {
        try {
            $repository->add($localId);
            $this->skippedResourcesCount++;
        } catch (WordPressDatabaseException $e) {
            if (StringHelper::startsWith($e->getMessage(), 'Duplicate entry')) {
                // already skipped from a previous batch
                $this->skippedResourcesCount++;

                return;
            }

            // don't increment skippedResourcesCount so makeOutcome can detect lack of progress
            SentryException::getNewInstance($e->getMessage(), $e);

            return;
        }

        if ($shouldLog) {
            $this->logSkippedProduct($localId, $v1RemoteId, $resourceType);
        }
    }

    /**
     * Logs a skipped product to the WooCommerce logger.
     *
     * @param int $localId
     * @param string $v1RemoteId
     * @param string $resourceType
     * @return void
     */
    protected function logSkippedProduct(int $localId, string $v1RemoteId, string $resourceType) : void
    {
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->warning(
                sprintf(
                    'V2 product mapping: skipped unmappable %s (local ID: %d, V1 UUID: %s)',
                    $resourceType,
                    $localId,
                    $v1RemoteId
                ),
                ['source' => 'godaddy-mwc-v2-mapping']
            );
        }
    }

    /** {@inheritDoc} */
    protected function getV1OriginString() : string
    {
        return 'catalog-api-v1-product';
    }

    /**
     * {@inheritDoc}
     *
     * @throws WordPressDatabaseException
     */
    protected function onAllBatchesCompleted() : void
    {
        $this->skippedSkuGroupRepository->deleteAll();
        $this->skippedSkuRepository->deleteAll();

        array_unshift($this->chain, MarkProductMappingAsCompleteJob::class);
    }
}
