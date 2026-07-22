<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

/**
 * Request class for communicating with the Commerce Catalog API.
 */
class Request extends AbstractRequest
{
    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $timeout = $this->getEnvironmentConfigValue('commerce.catalog.api.timeout');

        $this->setTimeout(TypeHelper::int($timeout, 10));
    }

    /**
     * Builds a valid url string with parameters.
     *
     * @return string
     * @throws Exception
     */
    public function buildUrlString() : string
    {
        /*
         * unset the locale to prevent a `locale` query arg from being added
         * this can be removed after decoupling from {@see GoDaddyRequest::buildUrlString()}
         */
        $this->locale = '';

        return parent::buildUrlString();
    }

    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return parent::getPathPrefix();
        }

        return '/v1/commerce/proxy/stores/'.$this->storeId;
    }
}
