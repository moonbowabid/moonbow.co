<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Http\GraphQL\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\GraphQL\Requests\AbstractRequest;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits\IsCommerceRequestTrait;

/**
 * GraphQL request class for communicating with the Commerce Catalog API v2.
 */
class Request extends AbstractRequest
{
    use IsCommerceRequestTrait {
        setStoreId as private traitSetStoreId;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return '/v2/commerce/stores/'.$this->storeId.'/catalog-subgraph';
        }

        return '/v1/commerce/proxy/v2/stores/'.$this->storeId.'/catalog-subgraph';
    }

    /**
     * {@inheritDoc}
     */
    public function setStoreId(string $value)
    {
        try {
            $this->addHeaders([
                $this->shouldUseGatewayUrl() ? 'x-store-id' : 'storeId' => $value,
            ]);
        } catch (Exception $e) {
            // Ignore.
        }

        return $this->traitSetStoreId($value); // @phpstan-ignore return.type (traitSetStoreId returns $this)
    }
}
