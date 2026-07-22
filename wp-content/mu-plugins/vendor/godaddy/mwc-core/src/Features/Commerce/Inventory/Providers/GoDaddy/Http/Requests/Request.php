<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

/**
 * Commerce Inventory Request class.
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

        if (in_array(ManagedWooCommerceRepository::getEnvironment(), [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)) {
            $timeout = Configuration::get('commerce.inventory.api.timeout.dev');
        } else {
            $timeout = Configuration::get('commerce.inventory.api.timeout.prod');
        }

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
