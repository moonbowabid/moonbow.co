<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Worldpay\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Cache\Types\FederationPartnerCache;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Http\Requests\FederationPartnerRequest;

/**
 * Repository for retrieving federation partner data from the MWC API.
 */
class FederationPartnerRepository
{
    use CanGetNewInstanceTrait;

    /**
     * Gets the federation partner UUID for a given customer ID.
     *
     * @param string $customerId
     * @return ?string
     */
    public function getFederationPartnerId(string $customerId) : ?string
    {
        $cache = FederationPartnerCache::getNewInstance($customerId);
        $cached = $cache->remember(static function () use ($customerId) {
            try {
                $response = FederationPartnerRequest::withAuth()
                    ->setPath("/customers/{$customerId}/federation-partner")
                    ->send();

                if ($response->isSuccess()) {
                    return TypeHelper::string(ArrayHelper::get($response->getBody() ?: [], 'federationPartnerId'), '');
                }
            } catch (Exception $e) {
                new SentryException($e->getMessage(), $e);
            }

            return null;
        });

        if ($cached === null) {
            // Short-term backoff so we don't hammer the API while it's unavailable.
            // @todo Remove once the circuit breaker from MWC-19547 is available.
            $cache->setFailureBackoff();
        }

        return TypeHelper::string($cached, '') ?: null;
    }
}
