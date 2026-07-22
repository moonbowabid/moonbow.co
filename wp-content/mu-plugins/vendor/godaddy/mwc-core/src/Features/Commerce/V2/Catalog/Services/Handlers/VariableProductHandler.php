<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RemoteProductIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\SkuGroupResponseToProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuGroupService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuService;

/**
 * Handler for simple product type operations.
 */
class VariableProductHandler extends AbstractProductTypeHandler
{
    public function __construct(SkuGroupService $skuGroupService, SkuService $skuService, SkuGroupResponseToProductBaseAdapter $productBaseAdapter)
    {
        parent::__construct($skuGroupService, $skuService, $productBaseAdapter);
    }

    /** {@inheritDoc} */
    protected function getExistingRemoteIds(CreateOrUpdateProductOperationContract $operation) : RemoteProductIds
    {
        return new RemoteProductIds([
            'skuGroupId' => $this->skuGroupService->getRemoteId($operation->getProduct()) ?: null,
            'skuId'      => null, // Variable products don't use SKU service
        ]);
    }

    /** {@inheritDoc} */
    protected function shouldCreate(RemoteProductIds $existingRemoteIds) : bool
    {
        return ! $existingRemoteIds->hasSkuGroupId();
    }

    /**
     * {@inheritDoc}
     * @return SkuGroupRequestOutput
     */
    protected function executeCreateOperations(CreateOrUpdateProductOperationContract $operation) : SkuGroupRequestOutput
    {
        // Variable products use only SKU Group service
        return $this->skuGroupService->create($operation);
    }

    /**
     * {@inheritDoc}
     * @return SkuGroupRequestOutput
     */
    protected function executeUpdateOperations(CreateOrUpdateProductOperationContract $operation) : SkuGroupRequestOutput
    {
        // Variable products use only SKU Group service
        $existingRemoteIds = $this->getExistingRemoteIds($operation);

        if (! $existingRemoteIds->hasSkuGroupId()) {
            throw new MissingProductRemoteIdException('Variable product missing SKU Group mapping for update');
        }

        // When the parent is being archived, use the dedicated archive mutation with cascadeArchive so the
        // platform archives every variant SKU in the group alongside the SKU Group itself.
        // Statuses listed here mirror CanMapProductStatusTrait::mapProductStatus()'s ARCHIVED branch.
        if (in_array($operation->getProduct()->getStatus(), ['trash', 'deleted'], true)) {
            return $this->skuGroupService->archive($existingRemoteIds->skuGroupId, true);
        }

        return $this->skuGroupService->update($operation, $existingRemoteIds->skuGroupId);
    }

    /**
     * {@inheritDoc}
     * @param SkuGroupRequestOutput&object $response
     */
    protected function extractPrimaryId(object $response) : string
    {
        if (empty($response->skuGroup->id)) {
            // This should never happen because we already check it in the request adapter, but just to appease phpstan
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        return $response->skuGroup->id;
    }

    /**
     * {@inheritDoc}
     * @param SkuGroupRequestOutput $response
     */
    protected function convertResponseToProductBase(object $response) : ProductBase
    {
        return $this->productBaseAdapter->convert($response);
    }
}
