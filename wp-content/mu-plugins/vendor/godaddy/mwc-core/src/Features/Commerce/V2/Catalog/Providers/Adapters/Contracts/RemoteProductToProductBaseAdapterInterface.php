<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Contracts;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

interface RemoteProductToProductBaseAdapterInterface
{
    /**
     * Converts a remote product object to a ProductBase object.
     *
     * @param object $remoteProductResponse Response object from the API, containing product data.
     * @return ProductBase The converted ProductBase object.
     * @throws AdapterException
     */
    public function convert(object $remoteProductResponse) : ProductBase;
}
