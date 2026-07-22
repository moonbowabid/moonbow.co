<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;

/**
 * Resolves database result rows into a map of local category IDs to their List remote IDs.
 *
 * @property ListMapRepository $listMapRepository
 */
trait CanResolveListRemoteIdsTrait
{
    /**
     * Converts database rows containing local_id into a map of local_id => list remote ID.
     *
     * @param array<mixed> $results
     * @return array<int, string>
     */
    protected function resolveListRemoteIds(array $results) : array
    {
        /** @var string[] $localIdStrings */
        $localIdStrings = array_column($results, 'local_id');
        $localIds = array_map('intval', $localIdStrings);

        if (empty($localIds)) {
            return [];
        }

        $listMappings = $this->listMapRepository->getMappingsByLocalIds($localIds);
        $mapped = [];

        foreach ($localIds as $localId) {
            $remoteId = $listMappings->getRemoteId($localId);

            if ($remoteId) {
                $mapped[$localId] = $remoteId;
            }
        }

        return $mapped;
    }
}
