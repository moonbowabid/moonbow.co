<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\DataObjects\EligibleApiVersionResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\Http\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;

/**
 * Helper class to determine which catalog API version a given store is eligible to use.
 * This communicates with the MWC API proxy to hit this endpoint:
 * /v1/commerce/catalog/internal/stores/{storeId}/eligibleVersion
 * That will return either `eligibleVersion: v1` or v2 accordingly.
 * When the API returns v1, we cache the result for a given length of time. As soon as it returns v2 we persist
 * this result indefinitely, meaning we never check the API again. This is because once a store switches over
 * to v2 it is not intended to ever go back to v1, so we do not need to check the API again.
 *
 * Cache is stored per store ID to support multi-store contexts.
 */
class EligibleApiVersionHelper
{
    /** @var string name of the wp_option key */
    protected const OPTION_KEY_NAME = 'mwc_commerce_eligible_catalog_api_version';

    /** @var int base cache TTL (6 hours); jitter may extend this by up to an additional 2-hour window */
    protected const CACHE_TTL = 6 * HOUR_IN_SECONDS;

    protected CommerceContextContract $commerceContext;
    protected CanGetJitterContract $jitterProvider;

    public function __construct(
        CommerceContextContract $commerceContext,
        CanGetJitterContract $jitterProvider
    ) {
        $this->commerceContext = $commerceContext;
        $this->jitterProvider = $jitterProvider;
    }

    /**
     * Determines whether the current store is eligible the v2 catalog API.
     * This utilizes the cache, if it exists.
     *
     * @return bool
     */
    public function isEligibleForV2() : bool
    {
        $versionData = $this->getCachedResponseData();

        try {
            if (! $versionData || $versionData->isExpired()) {
                $versionData = $this->getEligibleVersionFromApi();
            }
        } catch(Exception $e) {
            $this->handleException($e, $versionData);
        }

        if ($versionData instanceof EligibleApiVersionResponse) {
            return $versionData->isEligibleForV2();
        }

        return false;
    }

    /**
     * Gets the cached response from the local database for the current store.
     *
     * @return EligibleApiVersionResponse|null
     */
    protected function getCachedResponseData() : ?EligibleApiVersionResponse
    {
        $storeId = $this->commerceContext->getStoreId();
        $option = get_option(static::OPTION_KEY_NAME, []);
        $storeData = ArrayHelper::get($option, $storeId);

        if (! is_array($storeData) || ! array_key_exists('eligibleVersion', $storeData)) {
            return null;
        }

        return EligibleApiVersionResponse::getNewInstance($storeData);
    }

    /**
     * Queries the remote API (MWC API proxy) to get the eligible version.
     *
     * @return EligibleApiVersionResponse
     * @throws Exception
     */
    protected function getEligibleVersionFromApi() : EligibleApiVersionResponse
    {
        $response = Request::withAuth()
            ->setStoreId($this->commerceContext->getStoreId())
            ->setMethod('get')
            ->send();

        return $this->convertAndCacheResponse($response->getBody());
    }

    /**
     * Converts the response into a DTO and saves the local cache.
     *
     * @param array<mixed>|null $responseBody
     * @return EligibleApiVersionResponse
     */
    protected function convertAndCacheResponse(?array $responseBody) : EligibleApiVersionResponse
    {
        $version = TypeHelper::string(ArrayHelper::get($responseBody, 'eligibleVersion'), 'v1');

        if ($version === 'v2') {
            // once we see "v2", we'll keep that forever!
            $expiresAt = null;
        } else {
            $expiresAt = $this->getNewCacheExpiry();
        }

        $response = new EligibleApiVersionResponse([
            'eligibleVersion' => $version,
            'expiresAt'       => $expiresAt,
            'lastCheckedAt'   => time(),
        ]);

        $this->updateLocalCache($response);

        return $response;
    }

    /**
     * Updates the option in the local database to persist/cache the result for the current store.
     */
    protected function updateLocalCache(EligibleApiVersionResponse $response) : void
    {
        $storeId = $this->commerceContext->getStoreId();
        $option = get_option(static::OPTION_KEY_NAME, []);

        if (! is_array($option)) {
            $option = [];
        }

        $option[$storeId] = $response->toArray();

        update_option(static::OPTION_KEY_NAME, $option);
    }

    /**
     * Handles exceptions by:
     *
     * 1. Reporting to Sentry
     * 2. Extending the cache expiry, which ensures we don't re-try the API immediately.
     *
     * @param Exception $e
     * @param EligibleApiVersionResponse|null $cachedData
     * @return void
     */
    protected function handleException(Exception $e, ?EligibleApiVersionResponse $cachedData) : void
    {
        // report but don't throw
        SentryException::getNewInstance($e->getMessage(), $e);

        // extend cache to prevent loading up on requests
        $newExpiresAt = $this->getNewCacheExpiry();

        if ($cachedData instanceof EligibleApiVersionResponse) {
            $cachedData->expiresAt = $newExpiresAt;
        } else {
            $cachedData = new EligibleApiVersionResponse([
                'eligibleVersion' => 'v1', // if we have no data, we have to assume v1
                'expiresAt'       => $newExpiresAt,
            ]);
        }

        $this->updateLocalCache($cachedData);
    }

    /**
     * Calculates a new expiration date for the cache, applying a jitter to prevent all requests coming in at once.
     */
    protected function getNewCacheExpiry() : int
    {
        $jitter = $this->jitterProvider->getJitter(2 * HOUR_IN_SECONDS);

        return time() + static::CACHE_TTL + $jitter;
    }
}
