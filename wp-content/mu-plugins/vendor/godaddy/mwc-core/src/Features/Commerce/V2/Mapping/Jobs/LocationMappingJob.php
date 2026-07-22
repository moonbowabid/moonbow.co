<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Jobs;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Repositories\LocationMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferences;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Services\LocationReferencesService;

/**
 * Location mapping job class.
 *
 * This job maps location data from the v1 API to the v2 Commerce API UUIDs.
 */
class LocationMappingJob extends AbstractMappingJob
{
    public const JOB_KEY = 'v2LocationMapping';

    /** @var LocationMapRepository location map repository -- for inserting new v2 records */
    protected LocationMapRepository $locationMapRepository;

    /** @var \GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LocationMapRepository location map repository -- for querying old v1 records */
    protected \GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LocationMapRepository $v1ResourceRepository;

    public function __construct(
        LocationMapRepository $locationMapRepository,
        \GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LocationMapRepository $v1ResourceRepository,
        LocationReferencesService $locationReferencesService
    ) {
        $this->locationMapRepository = $locationMapRepository;
        $this->v1ResourceRepository = $v1ResourceRepository;
        $this->referencesService = $locationReferencesService;

        $this->setJobSettings($this->configureJobSettings());
    }

    /** {@inheritDoc} */
    protected function getLocalResourceMapsByLocalIds(array $localIds) : ResourceMapCollection
    {
        return $this->v1ResourceRepository->getMappingsByLocalIds($localIds);
    }

    /** {@inheritDoc} */
    protected function getUnmappedLocalIdsSqlString() : string
    {
        $db = DatabaseRepository::instance();

        $locationResourceTypeId = $this->locationMapRepository->getResourceTypeId();
        $mappedLocationIds = TypeHelper::string($db->prepare(
            /* @phpstan-ignore-next-line the only reason it's not a literal string is because we use constants to reference table/column names */
            $this->locationMapRepository->getMappedLocalIdsForResourceTypeQuery(),
            $locationResourceTypeId
        ), '');

        $v1LocationResourceTypeId = $this->v1ResourceRepository->getResourceTypeId();
        $resourceMapsTable = CommerceTables::ResourceMap;

        // Example:
        // SELECT godaddy_mwc_commerce_map_ids.local_id
        // FROM godaddy_mwc_commerce_map_ids
        // WHERE godaddy_mwc_commerce_map_ids.resource_type_id = 1
        //     AND godaddy_mwc_commerce_map_ids.local_id NOT IN (SELECT local_id FROM godaddy_mwc_commerce_map_ids WHERE resource_type_id = 11)
        // LIMIT 50
        return "
        SELECT {$resourceMapsTable}.local_id
        FROM {$resourceMapsTable}
        WHERE {$resourceMapsTable}.resource_type_id = {$v1LocationResourceTypeId}
            AND {$resourceMapsTable}.local_id NOT IN ({$mappedLocationIds})
        LIMIT %d
        ";
    }

    /**
     * {@inheritDoc}
     * @param LocationReferences[] $references
     */
    protected function buildReferenceMap(array $references, ResourceMapCollection $resourceMapCollection) : array
    {
        $resourceMaps = [];

        foreach ($references as $locationReference) {
            $v1Id = $this->getV1Reference($locationReference->locationReferences);
            if (! $v1Id) {
                continue;
            }

            $localId = $resourceMapCollection->getLocalId($v1Id);
            if (! $localId) {
                continue;
            }

            $resourceMaps[] = new ResourceMap([
                'commerceId'   => $locationReference->locationId,
                'localId'      => $localId,
                'resourceType' => CommerceResourceTypes::Location,
            ]);
        }

        return $resourceMaps;
    }

    /** {@inheritDoc} */
    protected function addLocalMappingRecord(ResourceMap $referenceMap) : void
    {
        $this->locationMapRepository->add($referenceMap->localId, $referenceMap->commerceId);
    }

    /** {@inheritDoc} */
    protected function getV1OriginString() : string
    {
        return 'catalog-api-v1-location';
    }

    /** {@inheritDoc} */
    protected function onAllBatchesCompleted() : void
    {
        update_option('mwc_v2_location_mapping_completed_at', date('Y-m-d H:i:s'));
    }
}
