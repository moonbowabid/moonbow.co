<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;

/**
 * Contract for handling local product deletion.
 */
interface HandleLocalProductDeletedContract
{
    /**
     * Handles the deletion of a local product by its ID.
     * This should perform any necessary cleanup operations on the local mapping table, related to this local product.
     *
     * @param int $localId
     * @return void
     * @throws WordPressDatabaseException
     */
    public function handle(int $localId) : void;
}
