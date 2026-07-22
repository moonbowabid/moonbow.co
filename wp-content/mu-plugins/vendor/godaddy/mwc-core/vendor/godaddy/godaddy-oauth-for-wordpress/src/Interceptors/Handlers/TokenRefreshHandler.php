<?php

namespace GoDaddy\WordPress\OAuth\Interceptors\Handlers;

use GoDaddy\WordPress\OAuth\Client\Exceptions\TokenRefreshException;
use GoDaddy\WordPress\OAuth\Services\TokenService;

/**
 * Handler for proactive token refresh.
 *
 * Executed by Action Scheduler every 45 minutes to refresh tokens
 * before they expire. Errors are handled silently since this is a
 * background optimization - the on-demand fallback (TokenService)
 * handles failures when tokens are actually needed.
 */
class TokenRefreshHandler extends AbstractInterceptorHandler
{
    /** @var TokenService */
    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Execute the proactive token refresh.
     *
     * @param mixed ...$args Arguments from Action Scheduler (unused).
     * @return void
     */
    public function run(...$args) : void
    {
        try {
            $this->tokenService->forceRefresh();
        } catch (TokenRefreshException $e) {
            // Background job - errors are handled silently.
            // TokenService already deletes tokens for permanent errors (invalid_grant, invalid_token).
        }
    }
}
