<?php

namespace GoDaddy\WordPress\OAuth\Interceptors;

use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\OAuth\Interceptors\Handlers\DisconnectHandler;

/**
 * Disconnect Interceptor.
 *
 * Intercepts OAuth disconnect requests using the WordPress admin_action_ hook pattern.
 * Delegates handling to DisconnectHandler.
 */
class DisconnectInterceptor extends AbstractInterceptor
{
    /**
     * Action name for the disconnect endpoint.
     */
    public const ACTION = 'gd_oauth_disconnect';

    /**
     * Register WordPress hooks.
     *
     * Hooks into admin_action_gd_oauth_disconnect to handle disconnect requests.
     *
     * @return void
     */
    public function addHooks() : void
    {
        add_action('admin_action_'.self::ACTION, [DisconnectHandler::class, 'handle']);
    }

    /**
     * Get the disconnect URL with nonce.
     *
     * @return string The disconnect URL with security nonce
     */
    public static function getDisconnectUrl() : string
    {
        return wp_nonce_url(
            admin_url('admin.php?action='.self::ACTION),
            self::ACTION
        );
    }
}
