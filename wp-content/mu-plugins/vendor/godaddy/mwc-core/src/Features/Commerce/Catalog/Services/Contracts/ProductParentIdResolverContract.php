<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdForParentException;

/**
 * Contract for resolving parent product ID conversions between local and remote systems.
 */
interface ProductParentIdResolverContract
{
    /**
     * Converts a remote parent UUID to a local parent ID.
     *
     * @param string $remoteParentId
     * @return int
     * @throws MissingProductLocalIdForParentException
     */
    public function getLocalParentId(string $remoteParentId) : int;

    /**
     * Converts a local parent ID to a remote parent UUID.
     *
     * @param int $localParentId
     * @return string
     * @throws MissingProductRemoteIdForParentException
     */
    public function getRemoteParentId(int $localParentId) : string;
}
