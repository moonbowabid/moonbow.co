<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Worldpay\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Request to retrieve the federation partner for a customer.
 */
class FederationPartnerRequest extends GoDaddyRequest
{
    use CanGetNewInstanceTrait;

    /**
     * Sends the request.
     *
     * @return ResponseContract
     * @throws Exception
     */
    public function send() : ResponseContract
    {
        if (empty($this->url)) {
            $this->setUrl(ManagedWooCommerceRepository::getApiUrl());
        }

        return parent::send();
    }
}
