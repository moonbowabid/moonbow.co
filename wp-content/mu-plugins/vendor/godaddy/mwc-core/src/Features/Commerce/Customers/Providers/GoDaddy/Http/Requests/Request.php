<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Http\Requests;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

/**
 * Commerce Customers Request class.
 */
class Request extends AbstractRequest
{
    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return parent::getPathPrefix();
        }

        return '/v1/commerce/customers/proxy/stores/'.$this->storeId;
    }
}
