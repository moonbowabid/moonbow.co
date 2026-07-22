<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\InsertLocalCategoryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Adapters\V2\ListWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\AbstractWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListTreeNodeQueryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Abstract class for handling webhooks related to Lists.
 */
abstract class AbstractListWebhookHandler extends AbstractWebhookHandler
{
    protected ListMapRepository $listMapRepository;
    protected ListTreeNodeQueryService $listTreeNodeQueryService;
    protected InsertLocalCategoryService $insertLocalCategoryService;

    public function __construct(
        ListMapRepository $listMapRepository,
        WebhooksRepository $webhooksRepository,
        ListTreeNodeQueryService $listTreeNodeQueryService,
        InsertLocalCategoryService $insertLocalCategoryService
    ) {
        $this->listMapRepository = $listMapRepository;
        $this->listTreeNodeQueryService = $listTreeNodeQueryService;
        $this->insertLocalCategoryService = $insertLocalCategoryService;

        parent::__construct($webhooksRepository);
    }

    /** {@inheritDoc} */
    protected function getLocalId(Webhook $webhook) : ?int
    {
        if (! $webhook->remoteResourceId) {
            throw new WebhookProcessingException('List ID not found in webhook data.');
        }

        return $this->getLocalIdFromMapRepository($this->listMapRepository, $webhook->remoteResourceId);
    }

    /**
     * Fetches the ListTreeNode for the list in this webhook.
     *
     * @param Webhook $webhook
     * @return ?ListTreeNode
     * @throws WebhookProcessingException
     */
    protected function getListTreeNode(Webhook $webhook) : ?ListTreeNode
    {
        if (! $webhook->remoteResourceId) {
            throw new WebhookProcessingException('List ID not found in webhook data.');
        }

        try {
            return $this->listTreeNodeQueryService->getByListId($webhook->remoteResourceId);
        } catch (CommerceExceptionContract $e) {
            throw new WebhookProcessingException('Failed to fetch ListTreeNode: '.$e->getMessage(), $e);
        }
    }

    /**
     * Converts the webhook payload to a Category DTO, enriched with hierarchy from the ListTreeNode.
     *
     * @param Webhook $webhook
     * @param ?ListTreeNode $listTreeNode
     * @return Category
     * @throws WebhookProcessingException
     */
    protected function getCategory(Webhook $webhook, ?ListTreeNode $listTreeNode = null) : Category
    {
        try {
            return ListWebhookPayloadAdapter::getNewInstance()
                ->convertResponse(
                    TypeHelper::array(ArrayHelper::get(json_decode($webhook->payload, true), 'data'), []),
                    $listTreeNode
                );
        } catch (MissingCategoryRemoteIdException $e) {
            throw new WebhookProcessingException($e->getMessage());
        }
    }

    /**
     * Ensures all ancestor categories exist locally before inserting a child.
     *
     * Walks the ListTreeNode's parent chain from root to immediate parent,
     * inserting any ancestors that don't have a local mapping yet.
     *
     * @param ?ListTreeNode $listTreeNode
     * @throws WebhookProcessingException
     */
    protected function maybeInsertAncestorCategories(?ListTreeNode $listTreeNode) : void
    {
        if (! $listTreeNode || ! $listTreeNode->parentListTreeNode) {
            return;
        }

        $ancestors = $this->buildAncestorChain($listTreeNode->parentListTreeNode);

        foreach ($ancestors as $ancestorNode) {
            $listId = $ancestorNode->list ? $ancestorNode->list->id : null;
            if (! $listId) {
                continue;
            }

            if ($this->listMapRepository->getLocalId($listId)) {
                continue;
            }

            $this->insertAncestorCategory($ancestorNode);
        }
    }

    /**
     * Builds an ordered ancestor chain from root to immediate parent.
     *
     * @param ListTreeNode $parentNode
     * @return array<ListTreeNode>
     */
    protected function buildAncestorChain(ListTreeNode $parentNode) : array
    {
        $ancestors = [];
        $currentNode = $parentNode;

        while ($currentNode !== null) {
            $ancestors[] = $currentNode;
            $currentNode = $currentNode->parentListTreeNode;
        }

        return array_reverse($ancestors);
    }

    /**
     * Inserts a single ancestor category from its ListTreeNode data.
     *
     * @param ListTreeNode $ancestorNode
     * @throws WebhookProcessingException
     */
    protected function insertAncestorCategory(ListTreeNode $ancestorNode) : void
    {
        $list = $ancestorNode->list;
        if (! $list || ! $list->id) {
            return;
        }

        $parentListId = ($ancestorNode->parentListTreeNode && $ancestorNode->parentListTreeNode->list)
            ? $ancestorNode->parentListTreeNode->list->id
            : null;

        try {
            $this->insertLocalCategoryService->insert(new Category([
                'altId'       => $list->name,
                'categoryId'  => $list->id,
                'createdAt'   => $list->createdAt,
                'depth'       => 0,
                'description' => $list->htmlDescription ?? $list->description,
                'name'        => $list->label,
                'parentId'    => $parentListId,
                'updatedAt'   => $list->updatedAt,
            ]));
        } catch (CommerceExceptionContract $e) {
            throw new WebhookProcessingException('Failed to insert ancestor category: '.$e->getMessage(), $e);
        }
    }
}
