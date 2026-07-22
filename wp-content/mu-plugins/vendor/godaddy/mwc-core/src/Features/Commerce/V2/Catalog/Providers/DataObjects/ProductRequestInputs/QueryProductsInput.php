<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AbstractPaginatedInput;

/**
 * Input for querying a page of products from the v2 Catalog GraphQL endpoint.
 */
class QueryProductsInput extends AbstractPaginatedInput
{
    /** @var string|null Optional SKUGroupStatusFilter value (e.g. ACTIVE, DRAFT, ARCHIVED). When null the adapter omits the filter. */
    public ?string $status = null;

    /**
     * @param array{
     *     storeId: string,
     *     cursor?: string|null,
     *     perPage?: int,
     *     status?: string|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct(array_merge([
            'perPage' => 50,
        ], $data));
    }
}
