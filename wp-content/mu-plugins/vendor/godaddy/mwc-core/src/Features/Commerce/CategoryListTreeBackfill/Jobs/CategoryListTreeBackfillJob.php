<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Traits\CanResolveListRemoteIdsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedListTreeNodeCategoriesRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListTreeNodeService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListTreeNodeMapRepository;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\BatchJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\HasJobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobOutcome;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\BatchJobTrait;

/**
 * Backfills ListTree and ListTreeNode resources for existing categories.
 *
 * @method BatchJobSettings getJobSettings()
 */
class CategoryListTreeBackfillJob implements QueueableJobContract, HasJobSettingsContract, BatchJobContract
{
    use BatchJobTrait;
    use CanResolveListRemoteIdsTrait;

    /** @var string unique identifier for the queue.jobs config */
    public const JOB_KEY = 'v2CategoryListTreeBackfill';

    protected ListMapRepository $listMapRepository;

    protected ListTreeNodeMapRepository $listTreeNodeMapRepository;

    protected ListTreeNodeService $listTreeNodeService;

    protected SkippedListTreeNodeCategoriesRepository $skippedResourcesRepository;

    public function __construct(
        ListMapRepository $listMapRepository,
        ListTreeNodeMapRepository $listTreeNodeMapRepository,
        ListTreeNodeService $listTreeNodeService,
        SkippedListTreeNodeCategoriesRepository $skippedResourcesRepository
    ) {
        $this->listMapRepository = $listMapRepository;
        $this->listTreeNodeMapRepository = $listTreeNodeMapRepository;
        $this->listTreeNodeService = $listTreeNodeService;
        $this->skippedResourcesRepository = $skippedResourcesRepository;

        $this->setJobSettings($this->configureJobSettings());
    }

    /**
     * Processes a single batch of categories needing tree resources.
     */
    protected function processBatch() : BatchJobOutcome
    {
        $unmappedCategories = $this->getUnmappedCategoryIds();

        $this->setAttemptedResourcesCount(count($unmappedCategories));

        foreach ($unmappedCategories as $localId => $listRemoteId) {
            $this->backfillTreeResources($localId, $listRemoteId);
        }

        return $this->makeOutcome();
    }

    /**
     * Creates tree resources for a single category.
     *
     * @throws WordPressDatabaseException
     */
    protected function backfillTreeResources(int $localId, string $listRemoteId) : void
    {
        try {
            $term = Term::getById($localId);

            if (! $term) {
                return;
            }

            $this->listTreeNodeService->createCategoryTreeResources($term, $listRemoteId);
        } catch (Exception|CommerceExceptionContract $e) {
            SentryException::getNewInstance("Failed to backfill tree resources for category {$localId}: ".$e->getMessage(), $e);

            $this->skippedResourcesRepository->add($localId);
        }
    }

    /**
     * Finds categories that have a List mapping but no ListTreeNode mapping.
     *
     * @return array<int, string> map of local_id => list remote ID
     */
    protected function getUnmappedCategoryIds() : array
    {
        $limit = $this->getJobSettings()->maxPerBatch;

        $skippedResourceTypeId = TypeHelper::int($this->skippedResourcesRepository->getResourceTypeId(), 0);

        $results = DatabaseRepository::getResults(
            $this->getUnmappedCategoryIdsSqlString(),
            [$skippedResourceTypeId, $limit]
        );

        return $this->resolveListRemoteIds($results);
    }

    /**
     * Builds the SQL to find categories with List mappings but no ListTreeNode mappings.
     */
    protected function getUnmappedCategoryIdsSqlString() : string
    {
        $db = DatabaseRepository::instance();
        $resourceMapsTable = CommerceTables::ResourceMap;

        $listResourceTypeId = TypeHelper::int($this->listMapRepository->getResourceTypeId(), 0);
        $listTreeNodeMappedIdsQuery = TypeHelper::string($db->prepare(
            // @phpstan-ignore argument.type (the only reason it's not a literal string is because we use constants to reference table/column names)
            $this->listTreeNodeMapRepository->getMappedLocalIdsForResourceTypeQuery(),
            TypeHelper::int($this->listTreeNodeMapRepository->getResourceTypeId(), 0)
        ), '');

        $skippedResourcesIdsSql = SkippedListTreeNodeCategoriesRepository::getSkippedResourcesIdsQuery();

        return "
            SELECT {$resourceMapsTable}.local_id
            FROM {$resourceMapsTable}
            INNER JOIN {$db->term_taxonomy} ON {$resourceMapsTable}.local_id = {$db->term_taxonomy}.term_id
            WHERE {$resourceMapsTable}.resource_type_id = {$listResourceTypeId}
                AND {$db->term_taxonomy}.taxonomy = 'product_cat'
                AND {$resourceMapsTable}.local_id NOT IN ({$listTreeNodeMappedIdsQuery})
                AND {$resourceMapsTable}.local_id NOT IN ({$skippedResourcesIdsSql})
            ORDER BY {$db->term_taxonomy}.parent ASC
            LIMIT %d
        ";
    }

    /**
     * {@inheritDoc}
     *
     * @throws WordPressDatabaseException
     */
    protected function onAllBatchesCompleted() : void
    {
        $this->skippedResourcesRepository->deleteAll();

        update_option('mwc_v2_category_list_tree_backfill_completed_at', date('Y-m-d H:i:s'));
    }
}
