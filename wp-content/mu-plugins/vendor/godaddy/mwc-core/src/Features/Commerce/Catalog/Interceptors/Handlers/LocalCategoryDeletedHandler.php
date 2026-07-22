<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\CategoryMapRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\ListTreeNodeService;

/**
 * Handler to respond to category deleted actions.
 */
class LocalCategoryDeletedHandler extends AbstractInterceptorHandler
{
    protected CategoryMapRepositoryContract $categoryMapRepository;

    protected CategoriesServiceContract $categoriesService;

    protected ListTreeNodeService $listTreeNodeService;

    /**
     * Constructor.
     *
     * @param CategoryMapRepositoryContract $categoryMapRepository
     * @param CategoriesServiceContract $categoriesService
     * @param ListTreeNodeService $listTreeNodeService
     */
    public function __construct(
        CategoryMapRepositoryContract $categoryMapRepository,
        CategoriesServiceContract $categoriesService,
        ListTreeNodeService $listTreeNodeService
    ) {
        $this->categoryMapRepository = $categoryMapRepository;
        $this->categoriesService = $categoriesService;
        $this->listTreeNodeService = $listTreeNodeService;
    }

    /**
     * Executes the callback for {@see wp_delete_term()} actions.
     *
     * When a local category is deleted, we propagate the delete to the remote v2 API (archiving the List)
     * and then remove the corresponding row from the {@see CommerceTables::ResourceMap} database table.
     * The local mapping cleanup always runs regardless of the remote call outcome.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        $localCategoryId = TypeHelper::int(ArrayHelper::get($args, 0), 0);

        // note: the hook name is already tied to the `product_cat` taxonomy, which is why we don't need a taxonomy check here
        // @see LocalCategoryDeletedInterceptor::addHooks()
        if (! $localCategoryId) {
            return;
        }

        // delete_{$taxonomy} fires AFTER the term is removed from the DB; WP passes a clone of the deleted term as arg 2
        // so we can still read its parent id without hitting the DB.
        $deletedTerm = ArrayHelper::get($args, 2);
        $parentLocalId = is_object($deletedTerm) ? TypeHelper::int($deletedTerm->parent ?? 0, 0) : 0;
        $parentLocalId = $parentLocalId ?: null;

        if (CatalogIntegration::shouldUseV2Api()) {
            try {
                $this->categoriesService->archiveCategory($localCategoryId);
            } catch (CategoryMappingNotFoundException $e) {
                // Expected when the category was never synced remotely; nothing to archive.
            } catch (Exception|CommerceExceptionContract $e) {
                SentryException::getNewInstance(sprintf('Failed to propagate category archive to remote: %s', $e->getMessage()), $e);
            }
        }

        try {
            $this->listTreeNodeService->deleteCategoryTreeResources($localCategoryId, $parentLocalId);
        } catch (Exception|CommerceExceptionContract $e) {
            // Tree cleanup failure should not block mapping deletion.
            SentryException::getNewInstance(sprintf('Failed to cleanup List Tree Nodes for deleted category: %s', $e->getMessage()), $e);
        }

        try {
            $this->categoryMapRepository->deleteByLocalId($localCategoryId);
        } catch (Exception|CommerceExceptionContract $e) {
            SentryException::getNewInstance('Failed to handle deleted category.', $e);
        }
    }
}
