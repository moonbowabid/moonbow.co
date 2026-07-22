<?php

namespace GoDaddy\WordPress\OAuth\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\OAuth\Client\OAuthClient;
use GoDaddy\WordPress\OAuth\Scopes\OAuthScopes;
use GoDaddy\WordPress\OAuth\Storage\Contracts\AuthorizationOperationRepositoryContract;

/**
 * Authorization Handler.
 *
 * Handles the initiation of the OAuth authorization flow.
 * Generates PKCE parameters, saves pending auth state, and
 * redirects to the OAuth provider's authorization endpoint.
 */
class AuthorizationHandler extends AbstractInterceptorHandler
{
    /**
     * OAuth client for generating authorization operations.
     *
     * @var OAuthClient
     */
    private OAuthClient $oauthClient;

    /**
     * Authorization operation repository for storing pending auth data.
     *
     * @var AuthorizationOperationRepositoryContract
     */
    private AuthorizationOperationRepositoryContract $authorizationOperationRepository;

    /**
     * Construct the handler with dependencies.
     *
     * @param OAuthClient $oauthClient OAuth client instance
     * @param AuthorizationOperationRepositoryContract $authorizationOperationRepository Authorization operation repository instance
     */
    public function __construct(OAuthClient $oauthClient, AuthorizationOperationRepositoryContract $authorizationOperationRepository)
    {
        $this->oauthClient = $oauthClient;
        $this->authorizationOperationRepository = $authorizationOperationRepository;
    }

    /**
     * Execute the authorization handler logic.
     *
     * Checks if this is an authorization request, validates the nonce
     * and user permissions, then initiates the OAuth flow.
     *
     * @param mixed ...$args Arguments from WordPress hook (unused)
     * @return void
     * @throws Exception
     */
    public function run(...$args) : void
    {
        // Verify nonce
        if (! $this->verifyNonce()) {
            $this->enqueueNotice('gd-oauth-error', Notice::TYPE_ERROR, __('Authorization failed. Please try again.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();

            return;
        }

        // Check user permissions
        if (! current_user_can('manage_options')) {
            $this->enqueueNotice('gd-oauth-error', Notice::TYPE_ERROR, __('Authorization failed. Please try again.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();

            return;
        }

        // Build callback URL
        $callbackUrl = admin_url('admin-post.php?action=gd_oauth_callback');

        // Get scopes as space-separated string
        $scope = implode(' ', OAuthScopes::all());

        // Get authorization operation (generates PKCE and state)
        $operation = $this->oauthClient->getAuthorizationOperation($callbackUrl, $scope);

        // Save the authorization operation
        $this->authorizationOperationRepository->save($operation);

        // Redirect to authorization URL
        wp_redirect($operation->getUrl());
        $this->terminate();
    }

    /**
     * Verify the WordPress nonce.
     *
     * @return bool True if nonce is valid, false otherwise
     */
    protected function verifyNonce() : bool
    {
        $nonce = isset($_GET['_wpnonce']) && is_string($_GET['_wpnonce'])
            ? $_GET['_wpnonce']
            : '';

        return (bool) wp_verify_nonce($nonce, 'gd_oauth_authorize');
    }
}
