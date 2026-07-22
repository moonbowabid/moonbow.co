<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Admin\GoDaddyStorePage;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\API\API;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\CheckProvisioningInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\OAuthFeatureGateInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\ProvisioningPollingInterceptor;
use GoDaddy\WordPress\MWC\Core\Traits\CanDetermineWhetherIsStagingSiteTrait;

/**
 * Feature that allows sites on Ultimate plans to connect themselves to the Commerce platform.
 */
class ConnectedCommerce extends AbstractFeature
{
    use HasComponentsFromContainerTrait;
    use CanDetermineWhetherIsStagingSiteTrait;

    /** @var class-string<ComponentContract>[] */
    protected array $componentClasses = [
        OAuthFeatureGateInterceptor::class,
        ProvisioningPollingInterceptor::class,
        CheckProvisioningInterceptor::class,
        GoDaddyStorePage::class,
        API::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'connected_commerce';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        if (static::isStagingSite()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function load() : void
    {
        try {
            $this->loadComponents();
        } catch (Exception $exception) {
            SentryException::getNewInstance("An error occurred trying to load components for the ConnectedCommerce feature: {$exception->getMessage()}", $exception);
        }
    }
}
