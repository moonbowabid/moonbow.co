<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AbstractPaginatedInput;

/**
 * Input for querying list tree nodes by their associated list IDs.
 */
class QueryListTreeNodesByListIdsInput extends AbstractPaginatedInput
{
    /** @var string[] */
    public array $listIds;

    /**
     * @param array{
     *     storeId: string,
     *     listIds: string[],
     *     cursor?: string|null,
     *     perPage: int
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
