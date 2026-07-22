<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\AddListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\ArchiveListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\CreateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\RemoveListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListTreeMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListTreeNodeMapRepository;

/**
 * Orchestrates ListTree and ListTreeNode operations for product categories.
 */
class ListTreeNodeService
{
    protected CommerceContextContract $commerceContext;

    protected CatalogProviderContract $catalogProvider;

    protected ListTreeMapRepository $listTreeMapRepository;

    protected ListTreeNodeMapRepository $listTreeNodeMapRepository;

    protected ListMapRepository $listMapRepository;

    public function __construct(
        CommerceContextContract $commerceContext,
        CatalogProviderContract $catalogProvider,
        ListTreeMapRepository $listTreeMapRepository,
        ListTreeNodeMapRepository $listTreeNodeMapRepository,
        ListMapRepository $listMapRepository
    ) {
        $this->commerceContext = $commerceContext;
        $this->catalogProvider = $catalogProvider;
        $this->listTreeMapRepository = $listTreeMapRepository;
        $this->listTreeNodeMapRepository = $listTreeNodeMapRepository;
        $this->listMapRepository = $listMapRepository;
    }

    /**
     * Creates tree resources for a category.
     *
     * @param Term $category
     * @param string $listRemoteId
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    public function createCategoryTreeResources(Term $category, string $listRemoteId) : void
    {
        $localId = $category->getId();

        if (! $localId) {
            return;
        }

        if (! $category->getParentId()) {
            $this->createRootTreeResources($category, $listRemoteId);
        } else {
            $this->createChildTreeResources($category, $listRemoteId);
        }
    }

    /**
     * Creates a ListTree and initial ListTreeNode for a root category.
     *
     * @param Term $category
     * @param string $listRemoteId
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    protected function createRootTreeResources(Term $category, string $listRemoteId) : void
    {
        $listTree = $this->catalogProvider->listTreeNodes()->createListTree(
            new CreateListTreeInput([
                'label'         => $category->getLabel() ?: $category->getName(),
                'description'   => $category->getDescription() ?: null,
                'status'        => 'ACTIVE',
                'storeId'       => $this->commerceContext->getStoreId(),
                'listTreeNodes' => [['listId' => $listRemoteId]],
            ])
        );

        if (! $listTree->id) {
            return;
        }

        /** @var int $localId */
        $localId = $category->getId();

        $this->listTreeMapRepository->add($localId, $listTree->id);

        $listTreeNodeId = $this->extractFirstNodeId($listTree->listTreeNodes);

        if ($listTreeNodeId) {
            $this->listTreeNodeMapRepository->add($localId, $listTreeNodeId);
        }
    }

    /**
     * Creates a ListTreeNode for a child category under its parent.
     *
     * @param Term $category
     * @param string $listRemoteId
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    protected function createChildTreeResources(Term $category, string $listRemoteId) : void
    {
        $this->maybeCreateParentTreeResources($category);

        /** @var int $parentId */
        $parentId = $category->getParentId();
        $parentNodeId = $this->listTreeNodeMapRepository->getRemoteId($parentId);

        if (! $parentNodeId) {
            return;
        }

        $listTreeNode = $this->catalogProvider->listTreeNodes()->addListTreeNodeToParent(
            new AddListTreeNodeInput([
                'listId'       => $listRemoteId,
                'parentNodeId' => $parentNodeId,
                'storeId'      => $this->commerceContext->getStoreId(),
            ])
        );

        if (! $listTreeNode->id) {
            return;
        }

        /** @var int $localId */
        $localId = $category->getId();

        $this->listTreeNodeMapRepository->add($localId, $listTreeNode->id);
    }

    /**
     * Ensures the parent chain has tree resources created.
     *
     * @param Term $childCategory
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    public function maybeCreateParentTreeResources(Term $childCategory) : void
    {
        $parentId = $childCategory->getParentId();

        if (! $parentId) {
            return;
        }

        if ($this->listTreeNodeMapRepository->getRemoteId($parentId)) {
            return;
        }

        $parentTerm = Term::getById($parentId);

        if (! $parentTerm) {
            return;
        }

        $parentListRemoteId = $this->listMapRepository->getRemoteId($parentId);

        if (! $parentListRemoteId) {
            SentryException::getNewInstance("Cannot create tree resources for parent category {$parentId}: no List remote ID found.");

            return;
        }

        $this->createCategoryTreeResources($parentTerm, $parentListRemoteId);
    }

    /**
     * Creates or updates tree resources for a category.
     *
     * @param Term $category
     * @param string $listRemoteId
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    public function createOrUpdateCategoryTreeResources(Term $category, string $listRemoteId) : void
    {
        $localId = $category->getId();

        if (! $localId) {
            return;
        }

        if ($this->listTreeNodeMapRepository->getRemoteId($localId)) {
            $this->updateCategoryTreeResources($category);
        } else {
            $this->createCategoryTreeResources($category, $listRemoteId);
        }
    }

    /**
     * Updates tree resources for a category.
     *
     * @param Term $category
     * @throws CommerceExceptionContract
     */
    public function updateCategoryTreeResources(Term $category) : void
    {
        $localId = $category->getId();

        if (! $localId) {
            return;
        }

        $listTreeId = $this->listTreeMapRepository->getRemoteId($localId);

        if (! $listTreeId) {
            return;
        }

        $this->catalogProvider->listTreeNodes()->updateListTree(
            new UpdateListTreeInput([
                'listTreeId'  => $listTreeId,
                'label'       => $category->getLabel() ?: $category->getName(),
                'description' => $category->getDescription() ?: null,
                'storeId'     => $this->commerceContext->getStoreId(),
            ])
        );
    }

    /**
     * Deletes tree resources for a category.
     *
     * The category's WP term has already been removed from the database by the time this is called,
     * so callers must pass the parent id captured from the delete hook for child-category resolution.
     *
     * @param int $localId
     * @param int|null $parentLocalId parent id of the deleted category, captured from the hook before WP removed the term
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    public function deleteCategoryTreeResources(int $localId, ?int $parentLocalId = null) : void
    {
        $listTreeNodeId = $this->listTreeNodeMapRepository->getRemoteId($localId);
        $listTreeId = $this->listTreeMapRepository->getRemoteId($localId);

        if ($listTreeNodeId) {
            $resolvedTreeId = $listTreeId ?: $this->resolveListTreeIdForCategory($parentLocalId);

            if ($resolvedTreeId) {
                $this->catalogProvider->listTreeNodes()->removeListTreeNodesFromListTree(
                    new RemoveListTreeNodesInput([
                        'listTreeId'      => $resolvedTreeId,
                        'listTreeNodeIds' => [$listTreeNodeId],
                        'storeId'         => $this->commerceContext->getStoreId(),
                    ])
                );
            }

            $this->listTreeNodeMapRepository->deleteByLocalId($localId);
        }

        if ($listTreeId) {
            $this->catalogProvider->listTreeNodes()->archiveListTree(
                new ArchiveListTreeInput([
                    'listTreeId' => $listTreeId,
                    'storeId'    => $this->commerceContext->getStoreId(),
                ])
            );

            $this->listTreeMapRepository->deleteByLocalId($localId);
        }
    }

    /**
     * Resolves the ListTree ID by walking up a parent chain, starting from the given parent id.
     *
     * @param int|null $parentId starting parent id (typically captured from the WP delete hook because the deleted term itself is no longer in the DB)
     * @return string|null
     */
    protected function resolveListTreeIdForCategory(?int $parentId) : ?string
    {
        while ($parentId) {
            $listTreeId = $this->listTreeMapRepository->getRemoteId($parentId);

            if ($listTreeId) {
                return $listTreeId;
            }

            $parentTerm = Term::getById($parentId);
            $parentId = $parentTerm ? $parentTerm->getParentId() : null;
        }

        return null;
    }

    /**
     * Extracts the first ListTreeNode ID from an array of nodes.
     *
     * @param array<\GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode>|null $nodes
     * @return string|null
     */
    protected function extractFirstNodeId(?array $nodes) : ?string
    {
        if (empty($nodes)) {
            return null;
        }

        return $nodes[0]->id ?? null;
    }
}
