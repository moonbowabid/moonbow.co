<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\RemoteCategoryNotFoundHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\Traits\CanInsertLocalCategoriesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\InsertLocalCategoryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListTreeNodeQueryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\UpdateLocalCategoryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Handler for "commerce.catalog.list.updated" webhooks.
 * Despite its name, this handler also deals with deleting and creating local categories, based on how the remote list status has changed.
 */
class ListUpdatedWebhookHandler extends AbstractListWebhookHandler
{
    use CanInsertLocalCategoriesTrait;

    protected UpdateLocalCategoryService $updateLocalCategoryService;
    protected RemoteCategoryNotFoundHelper $remoteCategoryNotFoundHelper;

    public function __construct(
        UpdateLocalCategoryService $updateLocalCategoryService,
        InsertLocalCategoryService $insertLocalCategoryService,
        RemoteCategoryNotFoundHelper $remoteCategoryNotFoundHelper,
        ListMapRepository $listMapRepository,
        WebhooksRepository $webhooksRepository,
        ListTreeNodeQueryService $listTreeNodeQueryService
    ) {
        $this->updateLocalCategoryService = $updateLocalCategoryService;
        $this->remoteCategoryNotFoundHelper = $remoteCategoryNotFoundHelper;

        parent::__construct($listMapRepository, $webhooksRepository, $listTreeNodeQueryService, $insertLocalCategoryService);
    }

    /**
     * {@inheritDoc}
     *
     * @throws AdapterException|WebhookProcessingException
     */
    public function handle(Webhook $webhook) : void
    {
        if (! $this->shouldHandle($webhook)) {
            return;
        }

        /*
         * How we handle the webhook depends on the status of the list:
         * - ARCHIVED: we delete the local category
         * - ACTIVE: we update or create the local category (need to handle creates here because you can update an archived list to be active again)
         * - DRAFT: we ignore it (WooCommerce doesn't support draft lists)
         */
        $status = $this->getListStatus($webhook);
        if ($status === 'ARCHIVED') {
            $this->handleDelete($webhook);
        } elseif ($status === 'ACTIVE') {
            $this->handleCreateOrUpdate($webhook);
        }
    }

    /**
     * Gets the status of the list from the webhook payload.
     *
     * @param Webhook $webhook
     * @return string
     * @throws WebhookProcessingException
     */
    protected function getListStatus(Webhook $webhook) : string
    {
        $payload = json_decode($webhook->payload, true);
        if (! is_array($payload)) {
            throw new WebhookProcessingException('Invalid webhook payload.');
        }

        $status = TypeHelper::string(ArrayHelper::get($payload, 'data.status'), '');
        if (empty($status)) {
            throw new WebhookProcessingException('Invalid webhook payload.');
        }

        return strtoupper($status);
    }

    /**
     * Handles deleting a local category.
     *
     * @param Webhook $webhook
     * @return void
     * @throws WebhookProcessingException
     */
    protected function handleDelete(Webhook $webhook) : void
    {
        $localId = $this->getLocalId($webhook);
        if ($localId) {
            $this->remoteCategoryNotFoundHelper->handle($localId);
        }
    }

    /**
     * Handles creating or updating a local category, based on whether or not we already have a local ID for it.
     *
     * @param Webhook $webhook
     * @return void
     * @throws AdapterException
     * @throws WebhookProcessingException
     */
    protected function handleCreateOrUpdate(Webhook $webhook) : void
    {
        $listTreeNode = $this->getListTreeNode($webhook);
        $localId = $this->getLocalId($webhook);
        $category = $this->getCategory($webhook, $listTreeNode);

        $this->maybeInsertAncestorCategories($listTreeNode);

        if ($localId) {
            $this->updateLocalCategoryService->update($category, $localId);
        } else {
            $this->insertLocalCategory($category);
        }
    }
}
