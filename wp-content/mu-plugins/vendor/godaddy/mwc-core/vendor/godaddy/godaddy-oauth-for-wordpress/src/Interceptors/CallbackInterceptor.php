<?php

namespace GoDaddy\WordPress\OAuth\Interceptors;

use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\OAuth\Interceptors\Handlers\CallbackHandler;

/**
 * Callback Interceptor.
 *
 * Intercepts OAuth callback requests after authorization.
 * Uses the admin_post action to handle the callback and
 * exchange the authorization code for tokens.
 */
class CallbackInterceptor extends AbstractInterceptor
{
    /**
     * Register WordPress hooks.
     *
     * Hooks into admin_post_gd_oauth_callback to handle callback requests.
     *
     * @return void
     */
    public function addHooks() : void
    {
        add_action('admin_post_gd_oauth_callback', [CallbackHandler::class, 'handle']);
    }
}
