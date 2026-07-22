<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Middleware;

use Closure;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts\WebhookMiddlewareContract;

/**
 * Parses remote resource IDs from payloads using the Standard Webhook spec.
 * @link https://github.com/standard-webhooks/standard-webhooks/
 */
class ParseResourceIdFromCommerceWebhookMiddleware implements WebhookMiddlewareContract
{
    /**
     * Attempts to parse the remote resource ID from the request body, and sets it on the request object.
     *
     * @param IncomingRequest $request
     * @param Closure $next
     * @return IncomingRequest
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest
    {
        if ($request instanceof IncomingWebhookRequest && $remoteResourceId = $this->getRemoteResourceIdFromRequest($request)) {
            $request->setRemoteResourceId($remoteResourceId);
        }

        return $next($request);
    }

    /**
     * Gets the remote resource ID from the request body.
     *
     * @param IncomingWebhookRequest $request
     * @return string|null
     */
    protected function getRemoteResourceIdFromRequest(IncomingWebhookRequest $request) : ?string
    {
        /** @var array<string, mixed> $decodedRequestBody */
        $decodedRequestBody = ArrayHelper::wrap(json_decode(TypeHelper::string($request->getBody(), ''), true));

        // catalog v1 webhooks (products and categories)
        if (StringHelper::startsWith(ArrayHelper::getStringValueForKey($decodedRequestBody, 'type'), 'commerce.product.')) {
            return ArrayHelper::getStringValueForKey($decodedRequestBody, 'data.productId') ?: null;
        }

        if (StringHelper::startsWith(ArrayHelper::getStringValueForKey($decodedRequestBody, 'type'), 'commerce.category.')) {
            return ArrayHelper::getStringValueForKey($decodedRequestBody, 'data.categoryId') ?: null;
        }

        // catalog v2 webhooks -- the primary resource is always in the "data" object, and uses the "id" property
        if (StringHelper::startsWith(ArrayHelper::getStringValueForKey($decodedRequestBody, 'type'), 'commerce.catalog.')) {
            return $this->getCatalogV2ResourceId($decodedRequestBody);
        }

        return null;
    }

    /**
     * Parses the resource ID from Catalog V2 requests.
     *
     * @param array<string, mixed> $decodedRequestBody
     * @return string|null
     */
    protected function getCatalogV2ResourceId(array $decodedRequestBody) : ?string
    {
        $dataId = ArrayHelper::getStringValueForKey($decodedRequestBody, 'data.id');
        if ($dataId) {
            return $dataId;
        }

        // some events are missing a `data.id` property, like media-related events. for those we'll use the sku or sku group
        $skuId = ArrayHelper::getStringValueForKey($decodedRequestBody, 'data.skuId');
        if ($skuId) {
            return $skuId;
        }

        $skuGroupId = ArrayHelper::getStringValueForKey($decodedRequestBody, 'data.skuGroupId');
        if ($skuGroupId) {
            return $skuGroupId;
        }

        return null;
    }
}
