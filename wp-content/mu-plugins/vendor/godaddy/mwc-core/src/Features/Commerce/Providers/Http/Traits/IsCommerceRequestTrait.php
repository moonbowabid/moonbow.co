<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetEnvironmentBasedConfigValueTrait;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasStoreIdentifierTrait;

trait IsCommerceRequestTrait
{
    use CanGetEnvironmentBasedConfigValueTrait;
    use HasStoreIdentifierTrait;

    /** @var bool|null */
    protected ?bool $cachedShouldUseGatewayUrl = null;

    /**
     * Gets the base URL for the API endpoint.
     *
     * Returns the GoDaddy Gateway URL when OAuth is active or the proxy URL otherwise.
     *
     * @return string
     */
    protected function getBaseUrl() : string
    {
        if ($this->shouldUseGatewayUrl()) {
            return TypeHelper::string($this->getEnvironmentConfigValue('commerce.godaddy_gateway.api.url'), '');
        }

        return TypeHelper::string($this->getEnvironmentConfigValue('commerce.gateway.api.url'), '');
    }

    /**
     * Gets the path prefix for the API endpoint.
     *
     * @return string
     */
    protected function getPathPrefix() : string
    {
        return "/v1/commerce/stores/{$this->storeId}";
    }

    /**
     * Determines whether this request should use the GoDaddy Gateway URL.
     *
     * Memoizes the result to avoid redundant container lookups and to guarantee
     * consistent routing when both getBaseUrl() and getPathPrefix() rely on this value.
     *
     * @return bool
     */
    protected function shouldUseGatewayUrl() : bool
    {
        if ($this->cachedShouldUseGatewayUrl !== null) {
            return $this->cachedShouldUseGatewayUrl;
        }

        try {
            ContainerFactory::getInstance()
                ->getSharedContainer()
                ->get(ThreeLeggedOAuthTokenProviderContract::class)
                ->getCredentials();

            return $this->cachedShouldUseGatewayUrl = true;
        } catch (AuthProviderException|CredentialsCreateFailedException|EntryNotFoundException|ContainerException $e) {
            return $this->cachedShouldUseGatewayUrl = false;
        }
    }

    /**
     * Sends the request.
     *
     * @return ResponseContract
     * @throws Exception
     */
    public function send() : ResponseContract
    {
        if (empty($this->url)) {
            $this->setUrl($this->getBaseUrl().$this->getPathPrefix());
        }

        return parent::send();
    }
}
