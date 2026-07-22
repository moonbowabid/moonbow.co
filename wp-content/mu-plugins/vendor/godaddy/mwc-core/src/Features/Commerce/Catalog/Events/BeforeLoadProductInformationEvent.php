<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasLocalIdsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasLocalIdsTrait;

/**
 * Event broadcasted when products are about to be loaded using a Woo/WordPress query.
 * This can be subscribed to if a feature needs to query the remote API in preparation to pre-warm the cache for those
 * resources and avoid N+1 issues.
 */
class BeforeLoadProductInformationEvent implements EventContract, HasLocalIdsContract
{
    use HasLocalIdsTrait;

    protected bool $withVariants = false;

    /**
     * Sets the local product IDs that are expected to be loaded.
     *
     * @param int[] $localIds
     * @return self
     */
    public static function withLocalIds(array $localIds)
    {
        return (new self())->setLocalIds($localIds);
    }

    /**
     * Sets whether variants (child products) are also expected to be loaded.
     *
     * @param bool $value
     * @return $this
     */
    public function setWithVariants(bool $value)
    {
        $this->withVariants = $value;

        return $this;
    }

    /**
     * Gets whether variants are also expected to be loaded.
     *
     * @return bool
     */
    public function getWithVariants() : bool
    {
        return $this->withVariants;
    }
}
