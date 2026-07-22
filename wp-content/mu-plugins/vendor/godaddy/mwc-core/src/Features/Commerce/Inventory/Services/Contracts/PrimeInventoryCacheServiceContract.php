<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

/**
 * Contract for priming inventory caches.
 *
 * This service batch-fetches inventory summaries and levels to populate the cache,
 * preventing N+1 API call problems when products are rendered.
 */
interface PrimeInventoryCacheServiceContract
{
    /**
     * Prime inventory caches for the given local product IDs.
     *
     * @param int[] $localProductIds
     * @return void
     */
    public function primeByLocalProductIds(array $localProductIds) : void;

    /**
     * Prime inventory caches for the given remote product IDs.
     *
     * @param string[] $remoteProductIds
     * @return void
     */
    public function primeByRemoteProductIds(array $remoteProductIds) : void;
}
