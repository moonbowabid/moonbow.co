<?php

namespace GoDaddy\WordPress\OAuth\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\OAuth\Interceptors\DisconnectInterceptor;
use GoDaddy\WordPress\OAuth\Services\TokenService;

/**
 * Disconnect Handler.
 *
 * Handles the OAuth disconnect flow. Verifies nonce and permissions,
 * revokes tokens on the server (best effort), deletes local tokens,
 * and redirects with a success notice.
 */
class DisconnectHandler extends AbstractInterceptorHandler
{
    /**
     * Token service for disconnect operations.
     *
     * @var TokenService
     */
    private TokenService $tokenService;

    /**
     * Construct the handler with dependencies.
     *
     * @param TokenService $tokenService Token service instance
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Execute the disconnect handler logic.
     *
     * Verifies nonce and permissions, disconnects via TokenService,
     * enqueues a success notice, and redirects to the admin page.
     *
     * @param mixed ...$args Arguments from WordPress hook (unused)
     * @return void
     */
    public function run(...$args) : void
    {
        check_admin_referer(DisconnectInterceptor::ACTION);

        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'godaddy-oauth-for-wordpress'));
        }

        $this->tokenService->disconnect();

        $this->enqueueNotice(
            'gd-oauth-disconnected',
            Notice::TYPE_SUCCESS,
            __('Successfully disconnected from GoDaddy.', 'godaddy-oauth-for-wordpress')
        );

        $this->redirectToAdminPage();
    }
}
