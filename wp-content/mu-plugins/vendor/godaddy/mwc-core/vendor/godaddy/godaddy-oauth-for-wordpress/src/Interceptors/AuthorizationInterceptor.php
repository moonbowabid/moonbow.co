<?php

namespace GoDaddy\WordPress\OAuth\Interceptors;

use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\OAuth\Interceptors\Handlers\AuthorizationHandler;

/**
 * Authorization Interceptor.
 *
 * Intercepts requests to initiate the OAuth authorization flow.
 * Uses the admin_post action to handle authorization requests,
 * ensuring only authenticated admin users can trigger the flow.
 */
class AuthorizationInterceptor extends AbstractInterceptor
{
    /**
     * Register WordPress hooks.
     *
     * Hooks into admin_post_gd_oauth_authorize to handle authorization requests.
     *
     * @return void
     */
    public function addHooks() : void
    {
        add_action('admin_post_gd_oauth_authorize', [AuthorizationHandler::class, 'handle']);
    }
}
