<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Contracts\RemoteProductToProductBaseAdapterInterface;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertSkuToProductBaseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;

/**
 * Adapter for converting SKU Response objects (with both SKU and SKU Group data) to ProductBase.
 *
 * Used for simple products and variations that receive both SKU and SKU Group data
 * from the GraphQL API in a single response.
 */
class SkuResponseToProductBaseAdapter implements RemoteProductToProductBaseAdapterInterface
{
    use CanConvertSkuToProductBaseTrait;

    /**
     * Convert a SkuResponse object to ProductBase.
     *
     * @param SkuRequestOutput&object $remoteProductResponse SkuResponse object with 'sku' and 'skuGroup' properties
     * @return ProductBase
     * @throws AdapterException
     */
    public function convert(object $remoteProductResponse) : ProductBase
    {
        /* @phpstan-ignore-next-line */
        if (! $remoteProductResponse instanceof SkuRequestOutput) {
            throw new AdapterException('Invalid remote product object. Expected instance of SkuRequestOutput.');
        }

        return $this->convertSkuToProductBase($remoteProductResponse->sku, $remoteProductResponse->skuGroup);
    }
}
