<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Worldpay\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Federation partner cache handler class.
 *
 * @method static static getNewInstance(string $customerId)
 */
class FederationPartnerCache extends Cache implements CacheableContract
{
    use CanGetNewInstanceTrait;

    /** @var int cache never expires; federation partner assignments are stable and should be kept indefinitely */
    protected $expires = 0;

    /**
     * Constructor.
     *
     * @param string $customerId GoDaddy customer ID.
     */
    final public function __construct(string $customerId)
    {
        $this->type('federation_partner');
        $this->key(sprintf('federation_partner_%s', strtolower($customerId)));
    }

    /**
     * Get an item from the cache, or execute the given callable and store the result.
     *
     * Overrides {@see \GoDaddy\WordPress\MWC\Common\Cache\AbstractCache::remember()} to skip
     * caching when the loader returns null, letting callers signal "do not cache" on failure
     * and sidestepping a WordPress transient round-trip bug where a stored null becomes an
     * empty string on a subsequent request.
     *
     * @todo Remove this override once MWC-19603 lands in mwc-common.
     *
     * @param callable $loader
     * @return mixed
     */
    public function remember(callable $loader)
    {
        $value = $this->get(null);

        if (null === $value) {
            $value = $loader();

            if (null !== $value) {
                $this->set($value);
            }
        }

        return $value;
    }

    /**
     * Caches an empty-string failure marker with a short TTL so repeated API failures
     * don't hammer the remote endpoint while it is unavailable.
     *
     * The TTL is jittered between 30 and 90 minutes so failing sites don't retry in lockstep.
     *
     * @todo Remove once the circuit breaker from MWC-19547 is available.
     */
    public function setFailureBackoff() : void
    {
        $originalExpires = $this->expires;

        try {
            // TODO: using the container directly is discouraged
            $jitterProvider = ContainerFactory::getInstance()->getSharedContainer()->get(CanGetJitterContract::class);

            $this->expires = 30 * MINUTE_IN_SECONDS + $jitterProvider->getJitter(60 * MINUTE_IN_SECONDS);
        } catch (ContainerException $e) {
            $this->expires = 60 * MINUTE_IN_SECONDS;
        }

        try {
            $this->set('');
        } finally {
            $this->expires = $originalExpires;
        }
    }
}
