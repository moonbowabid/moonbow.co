<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Traits;

/**
 * Provides transient-based update locking for Commerce resource services.
 *
 * Implementing classes must define: protected const UPDATE_LOCK_TRANSIENT_PREFIX
 */
trait HasUpdateLockTrait
{
    protected function setUpdateLock(string $resourceId) : void
    {
        set_transient($this->getUpdateLockKey($resourceId), '1', 15);
    }

    public function hasUpdateLock(string $resourceId) : bool
    {
        return (bool) get_transient($this->getUpdateLockKey($resourceId));
    }

    protected function getUpdateLockKey(string $resourceId) : string
    {
        /** @var string $prefix */
        $prefix = static::UPDATE_LOCK_TRANSIENT_PREFIX;

        return $prefix.$resourceId;
    }
}
