<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RemoteProductIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\SkuResponseToProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuGroupService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuService;

/**
 * Handler for simple product type operations.
 */
class SimpleProductHandler extends AbstractProductTypeHandler
{
    public function __construct(SkuGroupService $skuGroupService, SkuService $skuService, SkuResponseToProductBaseAdapter $productBaseAdapter)
    {
        parent::__construct($skuGroupService, $skuService, $productBaseAdapter);
    }

    /** {@inheritDoc} */
    protected function getExistingRemoteIds(CreateOrUpdateProductOperationContract $operation) : RemoteProductIds
    {
        return new RemoteProductIds([
            'skuGroupId' => $this->skuGroupService->getRemoteId($operation->getProduct()) ?: null,
            'skuId'      => $this->skuService->getRemoteId($operation->getProduct()) ?: null,
        ]);
    }

    /** {@inheritDoc} */
    protected function shouldCreate(RemoteProductIds $existingRemoteIds) : bool
    {
        // Use create flow for any missing resources (handles partial states)
        // Only use update flow when both resources exist
        return ! $existingRemoteIds->hasAllIds();
    }

    /**
     * Determines if we need to create a SKU Group.
     *
     * @param RemoteProductIds $existingRemoteIds
     * @return bool
     * @phpstan-assert-if-true null $existingRemoteIds->skuGroupId
     */
    protected function shouldCreateSkuGroup(RemoteProductIds $existingRemoteIds) : bool
    {
        return ! $existingRemoteIds->hasSkuGroupId();
    }

    /**
     * Determines if we need to create a SKU.
     *
     * @param RemoteProductIds $existingRemoteIds
     * @return bool
     * @phpstan-assert-if-true null $existingRemoteIds->skuId
     */
    protected function shouldCreateSku(RemoteProductIds $existingRemoteIds) : bool
    {
        return ! $existingRemoteIds->hasSkuId();
    }

    /**
     * Note: We'll end up here if we're missing either SKU Group or SKU (or both). This means the method must account
     * for inconsistent states where we are creating one resource, but updating the other. This situation is unique
     * to simple products only, which is the only one that maps two resources to one local entity.
     *
     * {@inheritDoc}
     * @return SkuRequestOutput
     */
    protected function executeCreateOperations(CreateOrUpdateProductOperationContract $operation) : SkuRequestOutput
    {
        $existingRemoteIds = $this->getExistingRemoteIds($operation);

        // Handle SKU Group (dependency for SKU)
        $this->updateOrCreateSkuGroup($operation, $existingRemoteIds);

        // Handle SKU and return its response
        return $this->updateOrCreateSku($operation, $existingRemoteIds);
    }

    /**
     * Creates or updates the SKU Group based on whether it already exists.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param RemoteProductIds $existingRemoteIds
     * @return void
     * @throws CommerceExceptionContract
     */
    protected function updateOrCreateSkuGroup(CreateOrUpdateProductOperationContract $operation, RemoteProductIds $existingRemoteIds) : void
    {
        if ($this->shouldCreateSkuGroup($existingRemoteIds)) {
            // Create SKU Group (dependency for SKU)
            $this->skuGroupService->create($operation);
        } else {
            // SKU Group exists, update it
            $this->skuGroupService->update($operation, $existingRemoteIds->skuGroupId);
        }
    }

    /**
     * Creates or updates the SKU based on whether it already exists.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param RemoteProductIds $existingRemoteIds
     * @return SkuRequestOutput
     * @throws CommerceExceptionContract|AdapterException
     */
    protected function updateOrCreateSku(CreateOrUpdateProductOperationContract $operation, RemoteProductIds $existingRemoteIds) : SkuRequestOutput
    {
        if ($this->shouldCreateSku($existingRemoteIds)) {
            // Create SKU
            return $this->skuService->create($operation);
        } else {
            // SKU exists, update it
            return $this->skuService->update($operation, $existingRemoteIds->skuId);
        }
    }

    /**
     * {@inheritDoc}
     * @return SkuRequestOutput
     */
    protected function executeUpdateOperations(CreateOrUpdateProductOperationContract $operation) : SkuRequestOutput
    {
        $existingRemoteIds = $this->getExistingRemoteIds($operation);

        if (! $existingRemoteIds->hasAllIds()) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        // Both resources exist (guaranteed by shouldCreate() logic)
        // @phpstan-ignore-next-line (shouldCreate() ensures both IDs exist)
        $this->skuGroupService->update($operation, $existingRemoteIds->skuGroupId);

        // @phpstan-ignore-next-line (shouldCreate() ensures both IDs exist)
        return $this->skuService->update($operation, $existingRemoteIds->skuId);
    }

    /**
     * @param SkuRequestOutput $response
     * {@inheritDoc}
     */
    protected function extractPrimaryId($response) : string
    {
        if (empty($response->sku->id)) {
            // This should never happen because we already check it in the request adapter, but just to appease phpstan
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        return $response->sku->id;
    }

    /**
     * {@inheritDoc}
     * @param SkuRequestOutput&object $response
     */
    protected function convertResponseToProductBase(object $response) : ProductBase
    {
        return $this->productBaseAdapter->convert($response);
    }
}
