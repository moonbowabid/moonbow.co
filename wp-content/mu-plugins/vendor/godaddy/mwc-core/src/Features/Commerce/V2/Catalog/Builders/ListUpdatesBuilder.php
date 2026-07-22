<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\ListUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\ListMapRepository;

class ListUpdatesBuilder
{
    /** @var ListMapRepository */
    protected ListMapRepository $listMapRepository;

    public function __construct(ListMapRepository $listMapRepository)
    {
        $this->listMapRepository = $listMapRepository;
    }

    /**
     * Build ListUpdates object for the given operation and SKU group.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return ListUpdates|null
     */
    public function build(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : ?ListUpdates
    {
        $listUpdates = ListUpdates::getNewInstance([]);

        if ($toRemove = $this->getListsToRemove($operation, $skuGroup)) {
            $listUpdates->toRemove = $toRemove;
        }

        if ($toAdd = $this->getListsToAdd($operation, $skuGroup)) {
            $listUpdates->toAdd = $toAdd;
        }

        return $listUpdates->hasUpdates() ? $listUpdates : null;
    }

    /**
     * Get list IDs to remove by comparing Commerce and WooCommerce categories.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return string[] Array of remote list IDs to remove
     */
    protected function getListsToRemove(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : array
    {
        $currentCommerceListIds = $this->getCurrentCommerceListIds($skuGroup);
        $localCategoryIds = $this->getProductCategoryIds($operation);

        // Get mapped remote list IDs for current local categories
        $currentMappedListIds = [];
        foreach ($localCategoryIds as $localCategoryId) {
            if ($remoteId = $this->listMapRepository->getRemoteId($localCategoryId)) {
                $currentMappedListIds[] = $remoteId;
            }
        }

        // Find Commerce lists that are no longer in the local product categories
        return array_values(array_diff($currentCommerceListIds, $currentMappedListIds));
    }

    /**
     * Get list IDs to add by comparing WooCommerce and Commerce categories.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return string[] Array of remote list IDs to add
     */
    protected function getListsToAdd(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : array
    {
        $currentCommerceListIds = $this->getCurrentCommerceListIds($skuGroup);
        $localCategoryIds = $this->getProductCategoryIds($operation);

        // Get mapped remote list IDs for local categories
        $targetMappedListIds = [];
        foreach ($localCategoryIds as $localCategoryId) {
            if ($remoteId = $this->listMapRepository->getRemoteId($localCategoryId)) {
                $targetMappedListIds[] = $remoteId;
            }
        }

        // Find local category lists that are not in Commerce
        return array_values(array_diff($targetMappedListIds, $currentCommerceListIds));
    }

    /**
     * Get current Commerce list IDs from SKU Group.
     *
     * @param SkuGroup $skuGroup
     * @return string[]
     */
    protected function getCurrentCommerceListIds(SkuGroup $skuGroup) : array
    {
        $listIds = [];

        foreach ($skuGroup->lists as $list) {
            if (! empty($list->id)) {
                $listIds[] = $list->id;
            }
        }

        return $listIds;
    }

    /**
     * Get category IDs from the WooCommerce product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return int[]
     */
    protected function getProductCategoryIds(CreateOrUpdateProductOperationContract $operation) : array
    {
        $product = $operation->getProduct();
        $categories = $product->getCategories();

        $categoryIds = [];
        foreach ($categories as $category) {
            if ($categoryId = $category->getId()) {
                $categoryIds[] = $categoryId;
            }
        }

        return $categoryIds;
    }
}
