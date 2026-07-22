<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RemoteProductIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\SkuResponseToProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuGroupService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuService;

/**
 * Handler for variation product type operations.
 */
class VariationProductHandler extends AbstractProductTypeHandler
{
    public function __construct(SkuGroupService $skuGroupService, SkuService $skuService, SkuResponseToProductBaseAdapter $productBaseAdapter)
    {
        parent::__construct($skuGroupService, $skuService, $productBaseAdapter);
    }

    /** {@inheritDoc} */
    protected function getExistingRemoteIds(CreateOrUpdateProductOperationContract $operation) : RemoteProductIds
    {
        return new RemoteProductIds([
            'skuGroupId' => null, // Variations don't use SKU Group service
            'skuId'      => $this->skuService->getRemoteId($operation->getProduct()) ?: null,
        ]);
    }

    /** {@inheritDoc} */
    protected function shouldCreate(RemoteProductIds $existingRemoteIds) : bool
    {
        return ! $existingRemoteIds->hasSkuId();
    }

    /**
     * {@inheritDoc}
     * @return SkuRequestOutput
     */
    protected function executeCreateOperations(CreateOrUpdateProductOperationContract $operation) : SkuRequestOutput
    {
        // Variations use only SKU service
        return $this->skuService->create($operation);
    }

    /**
     * {@inheritDoc}
     * @return SkuRequestOutput
     */
    protected function executeUpdateOperations(CreateOrUpdateProductOperationContract $operation) : SkuRequestOutput
    {
        // Variations use only SKU service
        $existingRemoteIds = $this->getExistingRemoteIds($operation);

        if (! $existingRemoteIds->hasSkuId()) {
            throw new MissingProductRemoteIdException('Variation product missing SKU mapping for update');
        }

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
