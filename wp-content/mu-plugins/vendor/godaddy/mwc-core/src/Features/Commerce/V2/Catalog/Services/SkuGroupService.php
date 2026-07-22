<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\ProductToSkuGroupAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\ArchiveSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping\SkuGroupMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Traits\HasUpdateLockTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Service for managing SKU Groups in the Commerce Catalog v2 API.
 *
 * Handles creation, updates, and mapping for SKU Group resources.
 */
class SkuGroupService
{
    use HasUpdateLockTrait;

    protected const UPDATE_LOCK_TRANSIENT_PREFIX = 'godaddy_mwc_sku_group_updating_';

    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var CatalogProviderContract */
    protected CatalogProviderContract $catalogProvider;

    /** @var SkuGroupMappingService */
    protected SkuGroupMappingService $skuGroupMappingService;

    /** @var SkuGroupRelationshipService */
    protected SkuGroupRelationshipService $skuGroupRelationshipService;

    protected ProductToSkuGroupAdapter $productToSkuGroupAdapter;

    public function __construct(
        CommerceContextContract $commerceContext,
        CatalogProviderContract $catalogProvider,
        SkuGroupMappingService $skuGroupMappingService,
        SkuGroupRelationshipService $skuGroupRelationshipService,
        ProductToSkuGroupAdapter $productToSkuGroupAdapter
    ) {
        $this->commerceContext = $commerceContext;
        $this->catalogProvider = $catalogProvider;
        $this->skuGroupMappingService = $skuGroupMappingService;
        $this->skuGroupRelationshipService = $skuGroupRelationshipService;
        $this->productToSkuGroupAdapter = $productToSkuGroupAdapter;
    }

    /**
     * Create a new SKU Group and save the mapping.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return SkuGroupRequestOutput The request output, including the SKU Group object
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function create(CreateOrUpdateProductOperationContract $operation) : SkuGroupRequestOutput
    {
        // Convert WooCommerce product to SKU Group data
        $createSkuGroupInput = $this->getCreateInput($operation);

        // Create via API provider
        $requestOutput = $this->catalogProvider->skuGroups()->create($createSkuGroupInput);

        if (! $requestOutput->skuGroup->id) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        // Save mapping
        $this->skuGroupMappingService->saveRemoteId($operation->getProduct(), $requestOutput->skuGroup->id);

        return $requestOutput;
    }

    /**
     * Get create input data for SKU Group creation.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateSkuGroupInput
     */
    protected function getCreateInput(CreateOrUpdateProductOperationContract $operation) : CreateSkuGroupInput
    {
        return new CreateSkuGroupInput([
            'storeId'  => $this->commerceContext->getStoreId(),
            'skuGroup' => $this->productToSkuGroupAdapter->convert($operation->getProduct()),
        ]);
    }

    /**
     * Update an existing SKU Group.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $skuGroupId
     * @return SkuGroupRequestOutput The request output, including the updated SKU Group object
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function update(CreateOrUpdateProductOperationContract $operation, string $skuGroupId) : SkuGroupRequestOutput
    {
        $updateSkuGroupInput = $this->getUpdateInput($operation, $skuGroupId);

        $this->setUpdateLock($skuGroupId);

        $requestOutput = $this->catalogProvider->skuGroups()->update($updateSkuGroupInput);

        if (! $requestOutput->skuGroup->id) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        $this->skuGroupRelationshipService->maybeUpdateRelationships($operation, $requestOutput->skuGroup);

        return $requestOutput;
    }

    /**
     * Get update input data for SKU Group updates.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $skuGroupId
     * @return UpdateSkuGroupInput
     */
    protected function getUpdateInput(CreateOrUpdateProductOperationContract $operation, string $skuGroupId) : UpdateSkuGroupInput
    {
        return new UpdateSkuGroupInput([
            'storeId'  => $this->commerceContext->getStoreId(),
            'skuGroup' => $this->productToSkuGroupAdapter->convert($operation->getProduct(), $skuGroupId),
        ]);
    }

    /**
     * Archives an existing SKU Group, optionally cascading the archive to every SKU in the group.
     *
     * @param string $skuGroupId remote SKU Group UUID to archive
     * @param bool $cascadeSkus when true, the platform archives every SKU in the group as part of the same call
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function archive(string $skuGroupId, bool $cascadeSkus = false) : SkuGroupRequestOutput
    {
        $this->setUpdateLock($skuGroupId);

        $requestOutput = $this->catalogProvider->skuGroups()->archive(new ArchiveSkuGroupInput([
            'storeId'     => $this->commerceContext->getStoreId(),
            'skuGroupId'  => $skuGroupId,
            'cascadeSkus' => $cascadeSkus,
        ]));

        if (! $requestOutput->skuGroup->id) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        return $requestOutput;
    }

    /**
     * Retrieves an existing SKU Group by its remote ID.
     *
     * @param string $skuGroupId
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract|MissingProductRemoteIdException
     */
    public function get(string $skuGroupId) : SkuGroupRequestOutput
    {
        return $this->catalogProvider->skuGroups()->get($this->getGetInput($skuGroupId));
    }

    /**
     * Get input data for retrieving a SKU Group.
     *
     * @param string $skuGroupId
     * @return GetSkuGroupInput
     */
    protected function getGetInput(string $skuGroupId) : GetSkuGroupInput
    {
        return new GetSkuGroupInput([
            'storeId'    => $this->commerceContext->getStoreId(),
            'skuGroupId' => $skuGroupId,
        ]);
    }

    /**
     * Check if a SKU Group exists for the given product.
     *
     * @param Product $product
     * @return string|null The remote SKU Group ID if it exists
     */
    public function getRemoteId(Product $product) : ?string
    {
        return $this->skuGroupMappingService->getRemoteId($product);
    }
}
