<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\Http;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

class Request extends AbstractRequest
{
    /** {@inheritDoc} */
    protected function getPathPrefix() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return "/v1/commerce/catalog/internal/stores/{$this->storeId}/eligibleVersion";
        }

        return "/v1/commerce/proxy/catalog/internal/stores/{$this->storeId}/eligibleVersion";
    }
}
