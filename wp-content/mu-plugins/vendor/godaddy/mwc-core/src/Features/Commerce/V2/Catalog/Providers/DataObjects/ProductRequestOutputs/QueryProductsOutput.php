<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AbstractPaginatedOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

/**
 * Output for one page of products from the v2 Catalog GraphQL endpoint.
 */
class QueryProductsOutput extends AbstractPaginatedOutput
{
    /** @var SkuGroup[] One page of products. Each entry is a fully-populated SkuGroup with its embedded skus, mediaObjects, channels, lists, and attributes. */
    public array $skuGroups = [];

    /**
     * @param array{
     *     skuGroups?: SkuGroup[],
     *     hasNextPage?: bool,
     *     endCursor?: string|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
