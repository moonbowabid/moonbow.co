<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

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

        $timeout = $this->getEnvironmentConfigValue('commerce.gateway.api.timeout');

        $this->setTimeout(TypeHelper::int($timeout, 10));
    }

    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return '/v1/apis';
        }

        return '/v1/commerce/proxy/apis';
    }
}
