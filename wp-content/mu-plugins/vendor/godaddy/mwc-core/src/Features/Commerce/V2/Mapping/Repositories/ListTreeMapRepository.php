<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories;

use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedCategoriesRepository;

/**
 * Repository map for ListTree resources (root categories).
 */
class ListTreeMapRepository extends CategoryMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::ListTree;

    /**
     * Gets the SQL string for the unmapped local IDs query, filtered to root categories only.
     */
    protected function getUnmappedLocalIdsSqlString() : string
    {
        $db = DatabaseRepository::instance();
        $skippedResourcesIdsSql = SkippedCategoriesRepository::getSkippedResourcesIdsQuery();

        return "
            SELECT {$db->terms}.term_id
            FROM {$db->terms}
            INNER JOIN {$db->term_taxonomy} ON({$db->terms}.term_id = {$db->term_taxonomy}.term_id)
            WHERE {$db->term_taxonomy}.taxonomy = %s
                AND {$db->term_taxonomy}.parent = 0
                AND {$db->terms}.term_id NOT IN ({$this->getMappedLocalIdsForResourceTypeQuery()})
                AND {$db->terms}.term_id NOT IN ({$skippedResourcesIdsSql})
            LIMIT %d
        ";
    }
}
