<?php

namespace GoDaddy\WordPress\OAuth\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\OAuth\Client\Exceptions\OAuthException;
use GoDaddy\WordPress\OAuth\Client\OAuthClient;
use GoDaddy\WordPress\OAuth\Services\TokenService;
use GoDaddy\WordPress\OAuth\Storage\Contracts\AuthorizationOperationRepositoryContract;
use GoDaddy\WordPress\OAuth\Storage\Contracts\TokenRepositoryContract;

/**
 * Callback Handler.
 *
 * Handles the OAuth callback after authorization.
 * Validates the state parameter, exchanges the authorization code
 * for tokens, and stores the tokens.
 */
class CallbackHandler extends AbstractInterceptorHandler
{
    /**
     * OAuth client for token exchange.
     *
     * @var OAuthClient
     */
    private OAuthClient $oauthClient;

    /**
     * Token repository for storing tokens.
     *
     * @var TokenRepositoryContract
     */
    private TokenRepositoryContract $tokenRepository;

    /**
     * Authorization operation repository for pending auth.
     *
     * @var AuthorizationOperationRepositoryContract
     */
    private AuthorizationOperationRepositoryContract $authorizationOperationRepository;

    /**
     * Token service for managing token state.
     *
     * @var TokenService
     */
    private TokenService $tokenService;

    /**
     * Construct the handler with dependencies.
     *
     * @param OAuthClient $oauthClient OAuth client instance
     * @param TokenRepositoryContract $tokenRepository Token repository instance
     * @param AuthorizationOperationRepositoryContract $authorizationOperationRepository Authorization operation repository instance
     * @param TokenService $tokenService Token service instance
     */
    public function __construct(
        OAuthClient $oauthClient,
        TokenRepositoryContract $tokenRepository,
        AuthorizationOperationRepositoryContract $authorizationOperationRepository,
        TokenService $tokenService
    ) {
        $this->oauthClient = $oauthClient;
        $this->tokenRepository = $tokenRepository;
        $this->authorizationOperationRepository = $authorizationOperationRepository;
        $this->tokenService = $tokenService;
    }

    /**
     * Execute the callback handler logic.
     *
     * Validates the callback request, exchanges the authorization code
     * for tokens, and stores them.
     *
     * @param mixed ...$args Arguments from WordPress hook (unused)
     * @return void
     */
    public function run(...$args) : void
    {
        // Check if this is a callback request with required parameters
        if (! $this->isValidCallbackRequest()) {
            return;
        }

        // Check user permissions
        if (! current_user_can('manage_options')) {
            $this->enqueueNotice('gd-oauth-error', Notice::TYPE_ERROR, __('Authorization failed. Please try again.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();

            return;
        }

        $code = $this->getCodeParam();
        $state = $this->getStateParam();

        // Get stored authorization operation
        $operation = $this->authorizationOperationRepository->get();

        if ($operation === null) {
            $this->enqueueNotice('gd-oauth-error', Notice::TYPE_ERROR, __('Authorization failed. Please try again.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();

            return;
        }

        // Validate state (timing-safe comparison)
        if (! hash_equals($operation->getState(), $state)) {
            $this->authorizationOperationRepository->delete();
            $this->enqueueNotice('gd-oauth-error', Notice::TYPE_ERROR, __('Authorization failed. Please try again.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();

            return;
        }

        try {
            // Exchange code for tokens
            $token = $this->oauthClient->getAccessToken($code, $operation->getCodeVerifier());

            // Save token
            $this->tokenRepository->save($token);

            // If no refresh token was issued (e.g., the user authorized without
            // the offline_access scope), mark the connection as degraded now so
            // the expiration warning banner surfaces immediately. Otherwise,
            // clear any previously-set degraded flag from an earlier session.
            if ($token->getRefreshToken() === '') {
                $this->tokenService->markConnectionAsDegraded();
            } else {
                $this->tokenService->clearConnectionDegraded();
            }

            // Clean up authorization operation
            $this->authorizationOperationRepository->delete();

            // Notify and redirect with success
            $this->enqueueNotice('gd-oauth-authorized', Notice::TYPE_SUCCESS, __('Authorization successful. You now have active tokens.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();
        } catch (OAuthException $e) {
            // Clean up authorization operation on failure
            $this->authorizationOperationRepository->delete();

            $this->enqueueNotice('gd-oauth-error', Notice::TYPE_ERROR, __('Authorization failed. Please try again.', 'godaddy-oauth-for-wordpress'));
            $this->redirectToAdminPage();
        }
    }

    /**
     * Check if this is a valid callback request.
     *
     * @return bool True if this is a callback request with required parameters
     */
    protected function isValidCallbackRequest() : bool
    {
        if ($this->getCodeParam() === '' || $this->getStateParam() === '') {
            return false;
        }

        return true;
    }

    /**
     * Get the authorization code from the request.
     *
     * @return string The authorization code
     */
    protected function getCodeParam() : string
    {
        return isset($_GET['code']) && is_string($_GET['code'])
            ? $_GET['code']
            : '';
    }

    /**
     * Get the state parameter from the request.
     *
     * @return string The state parameter
     */
    protected function getStateParam() : string
    {
        return isset($_GET['state']) && is_string($_GET['state'])
            ? $_GET['state']
            : '';
    }
}
