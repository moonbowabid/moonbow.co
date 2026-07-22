<?php

namespace GoDaddy\WordPress\OAuth\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler as CommonAbstractInterceptorHandler;
use GoDaddy\WordPress\OAuth\Admin\ConnectionPage;

/**
 * Abstract base class for OAuth interceptor handlers.
 *
 * Extends mwc-common's AbstractInterceptorHandler to inherit container
 * resolution and Sentry error reporting, while adding OAuth-specific
 * helper methods for notices and redirects.
 */
abstract class AbstractInterceptorHandler extends CommonAbstractInterceptorHandler
{
    /**
     * Store a pending admin notice in wp_options for display after redirect.
     *
     * The notice is persisted so it survives the redirect and is picked up
     * by ConnectionPage on the next admin page load.
     *
     * @param string $id Unique notice identifier
     * @param string $type Notice type (use Notice::TYPE_* constants)
     * @param string $content Notice message content
     * @return void
     */
    protected function enqueueNotice(string $id, string $type, string $content) : void
    {
        update_option(ConnectionPage::PENDING_NOTICE_OPTION, [
            'id'      => $id,
            'type'    => $type,
            'content' => $content,
        ]);
    }

    /**
     * Redirect to the admin connection page.
     *
     * @return void
     */
    protected function redirectToAdminPage() : void
    {
        wp_safe_redirect(admin_url('options-general.php?page='.ConnectionPage::PAGE_SLUG));
        $this->terminate();
    }

    /**
     * Terminate script execution.
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function terminate() : void
    {
        exit;
    }
}
