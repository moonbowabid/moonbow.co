<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\Requests;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\GraphQL\Requests\AbstractRequest;

class Request extends AbstractRequest
{
    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return '/v1/commerce/order-subgraph';
        }

        return '/v1/commerce/proxy/order-subgraph';
    }
}
