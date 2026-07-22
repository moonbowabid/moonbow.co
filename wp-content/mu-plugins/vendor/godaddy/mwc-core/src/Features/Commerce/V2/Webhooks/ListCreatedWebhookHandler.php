<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\Traits\CanInsertLocalCategoriesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\InsertLocalCategoryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListTreeNodeQueryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Handler for "commerce.catalog.list.created" webhooks.
 */
class ListCreatedWebhookHandler extends AbstractListWebhookHandler
{
    use CanInsertLocalCategoriesTrait;

    public function __construct(
        ListMapRepository $listMapRepository,
        WebhooksRepository $webhooksRepository,
        ListTreeNodeQueryService $listTreeNodeQueryService,
        InsertLocalCategoryService $insertLocalCategoryService
    ) {
        parent::__construct($listMapRepository, $webhooksRepository, $listTreeNodeQueryService, $insertLocalCategoryService);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldHandle(Webhook $webhook) : bool
    {
        if ($this->getLocalId($webhook)) {
            // category already exists
            return false;
        }

        return parent::shouldHandle($webhook);
    }

    /**
     * Determines if the category described in the payload can/should be created locally.
     *
     * @param string $payload
     * @return bool
     */
    protected function isLocallyCreatable(string $payload) : bool
    {
        $payload = json_decode($payload, true);
        if (! is_array($payload)) {
            return false;
        }

        $status = TypeHelper::string(ArrayHelper::get($payload, 'data.status'), '');

        // The API supports draft lists, but WooCommerce doesn't, so we'll ignore those for now.
        return strtoupper($status) === 'ACTIVE';
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Webhook $webhook) : void
    {
        if (! $this->shouldHandle($webhook) || ! $this->isLocallyCreatable($webhook->payload)) {
            return;
        }

        $this->insertLocalCategory($this->getCategory($webhook));
    }
}
