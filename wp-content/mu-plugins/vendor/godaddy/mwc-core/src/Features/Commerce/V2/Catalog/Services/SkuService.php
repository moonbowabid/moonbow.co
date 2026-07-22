<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\ProductToSkuAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductMediaObjectsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping\SkuMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Traits\HasUpdateLockTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Service for managing SKUs in the Commerce Catalog v2 API.
 *
 * Handles creation, updates, and mapping for SKU resources.
 */
class SkuService
{
    use CanConvertProductMediaObjectsTrait;
    use HasUpdateLockTrait;

    protected const UPDATE_LOCK_TRANSIENT_PREFIX = 'godaddy_mwc_sku_updating_';

    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var CatalogProviderContract */
    protected CatalogProviderContract $catalogProvider;

    /** @var SkuMappingService */
    protected SkuMappingService $skuMappingService;

    /** @var ProductToSkuAdapter */
    protected ProductToSkuAdapter $productToSkuAdapter;

    protected SkuRelationshipService $skuRelationshipService;

    public function __construct(
        CommerceContextContract $commerceContext,
        CatalogProviderContract $catalogProvider,
        SkuMappingService $skuMappingService,
        ProductToSkuAdapter $productToSkuAdapter,
        SkuRelationshipService $skuRelationshipService
    ) {
        $this->commerceContext = $commerceContext;
        $this->catalogProvider = $catalogProvider;
        $this->skuMappingService = $skuMappingService;
        $this->productToSkuAdapter = $productToSkuAdapter;
        $this->skuRelationshipService = $skuRelationshipService;
    }

    /**
     * Create a new SKU and save the mapping.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return SkuRequestOutput object with skuGroup and sku properties
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function create(CreateOrUpdateProductOperationContract $operation) : SkuRequestOutput
    {
        // Convert WooCommerce product to SKU data
        $createSkuInput = $this->getCreateInput($operation);

        // Create via API provider (response will include SKU Group and SKU data)
        $skuResponse = $this->catalogProvider->skus()->create($createSkuInput);

        if (! $skuResponse->sku->id) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        // Save mapping
        $this->skuMappingService->saveRemoteId($operation->getProduct(), $skuResponse->sku->id);

        return $skuResponse;
    }

    /**
     * Update an existing SKU.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $skuId
     * @return SkuRequestOutput object with skuGroup and sku properties
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function update(CreateOrUpdateProductOperationContract $operation, string $skuId) : SkuRequestOutput
    {
        $updateSkuInput = $this->getUpdateInput($operation, $skuId);

        $this->setUpdateLock($skuId);

        $skuResponse = $this->catalogProvider->skus()->update($updateSkuInput);

        if (! $skuResponse->sku->id) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        $this->skuRelationshipService->maybeUpdateRelationships($operation, $skuResponse->sku);

        return $skuResponse;
    }

    /**
     * Retrieves an existing SKU by its remote ID.
     *
     * @param string $skuId The remote SKU ID
     * @return SkuRequestOutput object with skuGroup and sku properties
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function get(string $skuId) : SkuRequestOutput
    {
        return $this->catalogProvider->skus()->get($this->getGetInput($skuId));
    }

    /**
     * Check if a SKU exists for the given product.
     *
     * @param Product $product
     * @return string|null The remote SKU ID if it exists
     */
    public function getRemoteId(Product $product) : ?string
    {
        return $this->skuMappingService->getRemoteId($product);
    }

    /**
     * Get create input data for SKU creation.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateSkuInput
     * @throws AdapterException
     */
    protected function getCreateInput(CreateOrUpdateProductOperationContract $operation) : CreateSkuInput
    {
        return new CreateSkuInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'sku'     => $this->productToSkuAdapter->convert($operation->getProduct()),
        ]);
    }

    /**
     * Get update input data for SKU updates.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $skuId
     * @return UpdateSkuInput
     * @throws AdapterException
     */
    protected function getUpdateInput(CreateOrUpdateProductOperationContract $operation, string $skuId) : UpdateSkuInput
    {
        return new UpdateSkuInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'sku'     => $this->productToSkuAdapter->convert($operation->getProduct(), $skuId),
        ]);
    }

    /**
     * Get input data for retrieving a SKU.
     *
     * @param string $skuId
     * @return GetSkuInput
     */
    protected function getGetInput(string $skuId) : GetSkuInput
    {
        return new GetSkuInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'skuId'   => $skuId,
        ]);
    }
}
