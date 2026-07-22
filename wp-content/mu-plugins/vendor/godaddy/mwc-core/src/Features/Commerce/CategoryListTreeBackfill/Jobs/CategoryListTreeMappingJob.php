<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\ListTreeNodeMapping;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Traits\CanResolveListRemoteIdsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListTreeMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListTreeNodeMapRepository;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\BatchJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\HasJobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobOutcome;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\BatchJobTrait;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Maps existing ListTree and ListTreeNode resources for categories that already have them remotely.
 *
 * @method BatchJobSettings getJobSettings()
 */
class CategoryListTreeMappingJob implements QueueableJobContract, HasJobSettingsContract, BatchJobContract
{
    use BatchJobTrait;
    use CanResolveListRemoteIdsTrait;

    /** @var string unique identifier for the queue.jobs config */
    public const JOB_KEY = 'v2CategoryListTreeMapping';

    protected const PAGE_SIZE = 50;

    protected CommerceContextContract $commerceContext;

    protected CatalogProviderContract $catalogProvider;

    protected ListMapRepository $listMapRepository;

    protected ListTreeMapRepository $listTreeMapRepository;

    protected ListTreeNodeMapRepository $listTreeNodeMapRepository;

    public function __construct(
        CommerceContextContract $commerceContext,
        CatalogProviderContract $catalogProvider,
        ListMapRepository $listMapRepository,
        ListTreeMapRepository $listTreeMapRepository,
        ListTreeNodeMapRepository $listTreeNodeMapRepository
    ) {
        $this->commerceContext = $commerceContext;
        $this->catalogProvider = $catalogProvider;
        $this->listMapRepository = $listMapRepository;
        $this->listTreeMapRepository = $listTreeMapRepository;
        $this->listTreeNodeMapRepository = $listTreeNodeMapRepository;

        $this->setJobSettings($this->configureJobSettings());
    }

    /**
     * Processes a single batch of categories needing tree mappings.
     */
    protected function processBatch() : BatchJobOutcome
    {
        $unmappedCategories = $this->getUnmappedCategoryListIds();

        $this->setAttemptedResourcesCount(count($unmappedCategories));

        if (! empty($unmappedCategories)) {
            $this->mapExistingTreeResources($unmappedCategories);
        }

        return $this->makeOutcome();
    }

    /**
     * Queries the API for existing ListTreeNodes and saves local mappings.
     *
     * @param array<int, string> $categoryListIds map of local_id => list remote ID
     */
    protected function mapExistingTreeResources(array $categoryListIds) : void
    {
        $listRemoteIds = array_values($categoryListIds);
        $localIdsByListId = array_flip($categoryListIds);

        $cursor = null;

        do {
            try {
                $output = $this->catalogProvider->listTreeNodes()->queryByListIds(
                    new QueryListTreeNodesByListIdsInput([
                        'storeId' => $this->commerceContext->getStoreId(),
                        'listIds' => $listRemoteIds,
                        'perPage' => self::PAGE_SIZE,
                        'cursor'  => $cursor,
                    ])
                );
            } catch (Exception|CommerceExceptionContract $e) {
                SentryException::getNewInstance('Failed to query list tree nodes for mapping: '.$e->getMessage(), $e);

                return;
            }

            foreach ($output->mappings as $mapping) {
                $this->saveMappingForNode($mapping, $localIdsByListId);
            }

            $cursor = $output->hasNextPage ? $output->endCursor : null;
        } while ($cursor);
    }

    /**
     * Saves ListTreeNode and ListTree mappings for a single node.
     *
     * @param ListTreeNodeMapping $mapping
     * @param array<string, int> $localIdsByListId
     */
    protected function saveMappingForNode(ListTreeNodeMapping $mapping, array $localIdsByListId) : void
    {
        $localId = $localIdsByListId[$mapping->listId] ?? null;

        if (! $localId) {
            return;
        }

        $this->saveMapping($localId, $this->listTreeNodeMapRepository, $mapping->listTreeNodeId);

        if (! $mapping->parentListTreeNodeId) {
            $this->saveMapping($localId, $this->listTreeMapRepository, $mapping->listTreeId);
        }
    }

    /**
     * Saves a single mapping, silently skipping duplicates.
     */
    protected function saveMapping(int $localId, AbstractResourceMapRepository $repository, string $remoteId) : void
    {
        try {
            $repository->add($localId, $remoteId);
        } catch (WordPressDatabaseException $e) {
            if (StringHelper::contains($e->getMessage(), 'Duplicate entry')) {
                return;
            }

            SentryException::getNewInstance("Failed to save tree mapping for category {$localId}: ".$e->getMessage(), $e);
        }
    }

    /**
     * Finds categories that have a List mapping but no ListTreeNode mapping.
     *
     * @return array<int, string> map of local_id => list remote ID
     * @throws WordPressDatabaseException
     */
    protected function getUnmappedCategoryListIds() : array
    {
        $limit = $this->getJobSettings()->maxPerBatch;

        $results = DatabaseRepository::getResults(
            $this->getUnmappedCategoryIdsSqlString(),
            [$limit]
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
            /* @phpstan-ignore-next-line the only reason it's not a literal string is because we use constants to reference table/column names */
            $this->listTreeNodeMapRepository->getMappedLocalIdsForResourceTypeQuery(),
            TypeHelper::int($this->listTreeNodeMapRepository->getResourceTypeId(), 0)
        ), '');

        return "
            SELECT {$resourceMapsTable}.local_id
            FROM {$resourceMapsTable}
            INNER JOIN {$db->term_taxonomy} ON {$resourceMapsTable}.local_id = {$db->term_taxonomy}.term_id
            WHERE {$resourceMapsTable}.resource_type_id = {$listResourceTypeId}
                AND {$db->term_taxonomy}.taxonomy = 'product_cat'
                AND {$resourceMapsTable}.local_id NOT IN ({$listTreeNodeMappedIdsQuery})
            ORDER BY {$db->term_taxonomy}.parent ASC
            LIMIT %d
        ";
    }
}
