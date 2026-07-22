<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\QueryProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\QueryProductsOutput;

/**
 * Contract for product gateways.
 */
interface ProductsGatewayContract
{
    /**
     * Queries one page of products from the v2 Catalog GraphQL endpoint.
     *
     * @param QueryProductsInput $input
     * @return QueryProductsOutput
     * @throws CommerceExceptionContract
     */
    public function query(QueryProductsInput $input) : QueryProductsOutput;
}
