<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AbstractPaginatedOutput;

/**
 * Output from querying list tree nodes by list IDs.
 */
class QueryListTreeNodesByListIdsOutput extends AbstractPaginatedOutput
{
    /** @var ListTreeNodeMapping[] */
    public array $mappings;

    /**
     * @param array{
     *     mappings: ListTreeNodeMapping[],
     *     hasNextPage?: bool,
     *     endCursor?: string|null
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
