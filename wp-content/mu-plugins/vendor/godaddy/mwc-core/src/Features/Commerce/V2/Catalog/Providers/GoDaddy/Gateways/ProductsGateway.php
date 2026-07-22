<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\ProductsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\QueryProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\QueryProductsOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Products\QueryProductsRequestAdapter;

/**
 * Gateway for querying products from the v2 Catalog GraphQL endpoint.
 */
class ProductsGateway extends AbstractGateway implements ProductsGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * Queries one page of products.
     *
     * @param QueryProductsInput $input
     * @return QueryProductsOutput
     * @throws CommerceExceptionContract
     */
    public function query(QueryProductsInput $input) : QueryProductsOutput
    {
        /** @var QueryProductsOutput $result */
        $result = $this->doAdaptedRequest(QueryProductsRequestAdapter::getNewInstance($input));

        return $result;
    }
}
