<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\OAuth\Scopes\OAuthScopes;

/**
 * Enables the OAuth package when the ConnectedCommerce feature loads.
 */
class OAuthFeatureGateInterceptor extends AbstractInterceptor
{
    /**
     * Filter name for controlling whether the OAuth package should initialize.
     */
    const FILTER_SHOULD_INITIALIZE = 'godaddy_oauth_should_initialize';

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup(self::FILTER_SHOULD_INITIALIZE)
            ->setHandler('__return_true')
            ->execute();

        OAuthScopes::registerMany(static::getRequiredScopes());
    }

    /**
     * Gets the OAuth scopes required by mwc-core.
     *
     * @return array<string>
     */
    public static function getRequiredScopes() : array
    {
        return [
            'offline_access',
            'commerce.category:read',
            'commerce.category:write',
            'commerce.channel:create',
            'commerce.channel:read',
            'commerce.channel:update',
            'commerce.customer:create',
            'commerce.customer:read',
            'commerce.customer:update',
            'commerce.fulfillment:create',
            'commerce.fulfillment:read',
            'commerce.fulfillment:update',
            'commerce.inventory-level:read',
            'commerce.inventory-level:write',
            'commerce.inventory-location:read',
            'commerce.inventory-location:write',
            'commerce.inventory-reservation:read',
            'commerce.inventory-reservation:write',
            'commerce.inventory-summary:read',
            'commerce.inventory-summary:write',
            'commerce.metafield:create',
            'commerce.metafield:delete',
            'commerce.metafield:read',
            'commerce.metafield:update',
            'commerce.onboarding-application:read',
            'commerce.onboarding-application:write',
            'commerce.order:cancel',
            'commerce.order:complete',
            'commerce.order:create',
            'commerce.order:read',
            'commerce.order:update',
            'commerce.order.fulfillment-aggregate-status:update',
            'commerce.product:read',
            'commerce.product:write',
            'commerce.store:read',
            'apis.webhook-subscriptions:read-write',
        ];
    }
}
