<?php

return [
    /*
     * Webhook Endpoints
     *
     * Each endpoint should have a unique namespace (array key) along with supported HTTP methods, middleware (optional),
     * and a handler.
     *
     * Middleware should implement the `WebhookMiddlewareContract` interface.
     */
    'endpoints' => [
        'commerce' => [
            'methods'    => ['POST'],
            'middleware' => [
                GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Http\ValidCommerceWebhookMiddleware::class,
                GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\ParseWebhookIdFromStandardWebhookMiddleware::class,
                GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\ParseOccurredAtFromStandardWebhookMiddleware::class,
                GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Middleware\ParseResourceIdFromCommerceWebhookMiddleware::class,
            ],
            'handler' => GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\WebhookHandler::class, // @TODO this is just an example; to be implemented in a MWC-16900
        ],
    ],

    /*
     * Legacy Webhook Endpoints
     *
     * These endpoints use the `/wc-api/` infrastructure, and should no longer be used.
     */
    'legacy-endpoints' => [
        [
            'namespace'  => 'poynt',
            'eventClass' => GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\WebhookReceivedEvent::class,
        ],
        [
            'namespace'  => 'marketplaces',
            'eventClass' => GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\WebhookReceivedEvent::class,
        ],
    ],

    /*
     * Received Webhooks Cleanup
     *
     * Controls the background cleanup of expired rows from the `{prefix}_godaddy_mwc_received_webhooks` table.
     * `cleanupBatchSize` and `cleanupBatchIntervalSeconds` are exposed for emergency tuning, not as product configuration.
     */
    'receivedWebhooks' => [
        'retentionDays' => [
            'default'  => 30,
            'override' => defined('MWC_RECEIVED_WEBHOOKS_RETENTION_DAYS')
                ? MWC_RECEIVED_WEBHOOKS_RETENTION_DAYS
                : null,
        ],
        'cleanupBatchSize'            => 1000,
        'cleanupBatchIntervalSeconds' => 60,
        'cleanupActionGroup'          => 'mwc_webhook_cleanup',
    ],
];
