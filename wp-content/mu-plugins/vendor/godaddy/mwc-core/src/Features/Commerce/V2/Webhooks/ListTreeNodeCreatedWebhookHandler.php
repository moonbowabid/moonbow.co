<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;

/**
 * Handler for "commerce.catalog.list-tree-node.created" webhooks.
 *
 * When a ListTreeNode is created with a parent, this indicates a category
 * has been placed in a hierarchy. We update the local category's parent,
 * and self-heal by inserting any missing categories in the chain (handles
 * out-of-order webhook delivery where list-tree-node.created arrives
 * before the corresponding list.created).
 */
class ListTreeNodeCreatedWebhookHandler extends AbstractListWebhookHandler
{
    /**
     * {@inheritDoc}
     *
     * @throws WebhookProcessingException
     */
    public function handle(Webhook $webhook) : void
    {
        if (! $this->shouldHandle($webhook)) {
            return;
        }

        $listId = $this->getListIdFromPayload($webhook);
        if (! $listId) {
            return;
        }

        $listTreeNode = $this->fetchListTreeNode($listId);
        if (! $listTreeNode) {
            return;
        }

        $this->maybeInsertAncestorCategories($listTreeNode);
        $this->maybeInsertCategoryFromNode($listTreeNode);

        $localId = $this->getLocalIdFromMapRepository($this->listMapRepository, $listId);
        if (! $localId) {
            return;
        }

        $this->updateCategoryParent($localId, $listTreeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldHandle(Webhook $webhook) : bool
    {
        if (! $this->getParentNodeIdFromPayload($webhook)) {
            return false;
        }

        return parent::shouldHandle($webhook);
    }

    /**
     * {@inheritDoc}
     */
    protected function getLocalId(Webhook $webhook) : ?int
    {
        $listId = $this->getListIdFromPayload($webhook);
        if (! $listId) {
            return null;
        }

        return $this->getLocalIdFromMapRepository($this->listMapRepository, $listId);
    }

    /**
     * Extracts the listId from the webhook payload.
     */
    protected function getListIdFromPayload(Webhook $webhook) : ?string
    {
        $payload = json_decode($webhook->payload, true);
        if (! is_array($payload)) {
            return null;
        }

        return TypeHelper::stringOrNull(ArrayHelper::get($payload, 'data.listId'));
    }

    /**
     * Extracts the parentNodeId from the webhook payload.
     */
    protected function getParentNodeIdFromPayload(Webhook $webhook) : ?string
    {
        $payload = json_decode($webhook->payload, true);
        if (! is_array($payload)) {
            return null;
        }

        return TypeHelper::stringOrNull(ArrayHelper::get($payload, 'data.parentNodeId'));
    }

    /**
     * Fetches the ListTreeNode for the given list ID.
     *
     * @throws WebhookProcessingException
     */
    protected function fetchListTreeNode(string $listId) : ?ListTreeNode
    {
        try {
            return $this->listTreeNodeQueryService->getByListId($listId);
        } catch (CommerceExceptionContract $e) {
            throw new WebhookProcessingException('Failed to fetch ListTreeNode: '.$e->getMessage(), $e);
        }
    }

    /**
     * Inserts the queried node's category locally if its list isn't yet mapped.
     *
     * Handles out-of-order delivery where list-tree-node.created arrives
     * before list.created has created the local term.
     *
     * @throws WebhookProcessingException
     */
    protected function maybeInsertCategoryFromNode(ListTreeNode $listTreeNode) : void
    {
        $list = $listTreeNode->list;
        if (! $list || ! $list->id) {
            return;
        }

        if ($this->listMapRepository->getLocalId($list->id)) {
            return;
        }

        $this->insertAncestorCategory($listTreeNode);
    }

    /**
     * Updates the local category's parent based on the ListTreeNode hierarchy.
     *
     * @param positive-int $localId
     */
    protected function updateCategoryParent(int $localId, ListTreeNode $listTreeNode) : void
    {
        $parentLocalId = $this->resolveParentLocalId($listTreeNode);

        CatalogIntegration::withoutWrites(
            fn () => TermsRepository::updateTerm($localId, CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY, ['parent' => $parentLocalId])
        );
    }

    /**
     * Resolves the parent's local term ID from the ListTreeNode hierarchy.
     */
    protected function resolveParentLocalId(ListTreeNode $listTreeNode) : int
    {
        $parentListId = $this->getParentListId($listTreeNode);
        if (! $parentListId) {
            return 0;
        }

        return TypeHelper::int($this->listMapRepository->getLocalId($parentListId), 0);
    }

    /**
     * Gets the parent's list ID from the ListTreeNode hierarchy.
     */
    protected function getParentListId(ListTreeNode $listTreeNode) : ?string
    {
        if (! $listTreeNode->parentListTreeNode || ! $listTreeNode->parentListTreeNode->list) {
            return null;
        }

        return $listTreeNode->parentListTreeNode->list->id;
    }
}
