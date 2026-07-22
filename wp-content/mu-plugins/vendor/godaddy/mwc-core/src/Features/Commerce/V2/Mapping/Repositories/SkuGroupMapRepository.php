<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedProductsRepository;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class SkuGroupMapRepository extends AbstractResourceMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::SkuGroup;

    /**
     * This is just overridden to make the method public.
     *
     * {@inheritDoc}
     */
    public function getMappedLocalIdsForResourceTypeQuery() : string
    {
        return parent::getMappedLocalIdsForResourceTypeQuery();
    }

    /**
     * Gets unmapped local IDs for products without SkuGroup mappings.
     *
     * @param int $limit
     * @return int[]
     */
    public function getUnmappedLocalIds(int $limit = 50) : array
    {
        $results = DatabaseRepository::getResults(
            $this->getUnmappedLocalIdsSqlString(),
            [
                CatalogIntegration::PRODUCT_POST_TYPE,
                $limit,
            ],
        );

        return array_map(static fn ($value) => TypeHelper::int($value, 0), array_column($results, 'ID'));
    }

    /**
     * Gets the SQL for the unmapped local IDs query.
     *
     * @return string
     */
    protected function getUnmappedLocalIdsSqlString() : string
    {
        $resourceTypeId = $this->getResourceTypeId();
        $db = DatabaseRepository::instance();

        $notInConditions = "{$db->posts}.ID NOT IN(
            SELECT local_id
            FROM ".AbstractResourceMapRepository::MAP_IDS_TABLE.'
            WHERE resource_type_id = '.intval($resourceTypeId).'
        )';

        // Use the Product resource type ID for the skipped resources subquery, not the SkuGroup type,
        // because SkippedProductsRepository stores entries under the Product resource type.
        $skippedProductsResourceTypeId = (new SkippedProductsRepository())->getResourceTypeId();

        $skippedResourcesIdsSql = TypeHelper::string($db->prepare(
            /* @phpstan-ignore-next-line the only reason it's not a literal string is because we use constants to reference table/column names */
            SkippedProductsRepository::getSkippedResourcesIdsQuery(),
            $skippedProductsResourceTypeId
        ), '');

        // Query for all products (simple or variable) not mapped to any of the provided resource types
        // Variations are excluded by the post_type filter (they're not top-level products)
        return "
            SELECT {$db->posts}.ID
            FROM {$db->posts}
            WHERE {$db->posts}.post_type = %s
                AND post_status NOT IN ('new', 'auto-draft')
                AND {$notInConditions}
                AND {$db->posts}.ID NOT IN({$skippedResourcesIdsSql})
            LIMIT %d
        ";
    }
}
