<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\ProductCreationValidator;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\CreateOrUpdateProductResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotCreatableException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanBroadcastResourceEventsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RemoteProductIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Contracts\RemoteProductToProductBaseAdapterInterface;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Contracts\ProductTypeHandlerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuGroupService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuService;

abstract class AbstractProductTypeHandler implements ProductTypeHandlerContract
{
    use CanBroadcastResourceEventsTrait;

    protected SkuGroupService $skuGroupService;
    protected SkuService $skuService;
    protected RemoteProductToProductBaseAdapterInterface $productBaseAdapter;

    public function __construct(
        SkuGroupService $skuGroupService,
        SkuService $skuService,
        RemoteProductToProductBaseAdapterInterface $productBaseAdapter
    ) {
        $this->skuGroupService = $skuGroupService;
        $this->skuService = $skuService;
        $this->productBaseAdapter = $productBaseAdapter;
    }

    /**
     * Updates or creates the resource in the remote API.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws AdapterException|CommerceExceptionContract|MissingProductRemoteIdException|ProductNotCreatableException|Exception
     */
    public function createOrUpdate(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        $existingRemoteIds = $this->getExistingRemoteIds($operation);
        $shouldCreate = $this->shouldCreate($existingRemoteIds);

        if ($shouldCreate) {
            return $this->create($operation);
        } else {
            return $this->update($operation);
        }
    }

    /**
     * Creates the resource in the remote API.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws AdapterException|CommerceExceptionContract|MissingProductRemoteIdException|ProductNotCreatableException|Exception
     */
    public function create(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        $this->validateShouldCreateProduct($operation);

        $response = $this->executeCreateOperations($operation);
        $primaryId = $this->extractPrimaryId($response);
        $product = $this->convertResponseToProductBase($response);

        return $this->makeCreateOrUpdateResponse($operation, $primaryId, $product, true);
    }

    /**
     * Updates the resource in the remote API.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    public function update(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        $response = $this->executeUpdateOperations($operation);
        $primaryId = $this->extractPrimaryId($response);
        $product = $this->convertResponseToProductBase($response);

        return $this->makeCreateOrUpdateResponse($operation, $primaryId, $product, false);
    }

    /**
     * Validates whether the product should be created in the remote system.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return void
     * @throws CommerceExceptionContract|Exception
     */
    protected function validateShouldCreateProduct(CreateOrUpdateProductOperationContract $operation) : void
    {
        $existingRemoteIds = $this->getExistingRemoteIds($operation);
        if (! $this->shouldCreate($existingRemoteIds)) {
            throw new CommerceException('Product already exists in remote system; cannot create duplicate.');
        }

        ProductCreationValidator::validateShouldCreateProduct($operation);
    }

    /**
     * Constructs the response for create or update operations and broadcasts relevant events.
     *
     * @param CreateOrUpdateProductOperationContract $operation original operation
     * @param non-empty-string $remoteProductId newly created or updated remote product ID
     * @param ProductBase $product DTO representing the created or updated product data
     * @param bool $isCreated whether the product was created (true) or updated (false)
     * @return CreateOrUpdateProductResponseContract
     */
    protected function makeCreateOrUpdateResponse(CreateOrUpdateProductOperationContract $operation, string $remoteProductId, ProductBase $product, bool $isCreated = true) : CreateOrUpdateProductResponseContract
    {
        if ($isCreated) {
            $event = ProductCreatedEvent::getNewInstance($operation->getProduct(), $remoteProductId, $product);
        } else {
            $event = ProductUpdatedEvent::getNewInstance(
                ProductAssociation::getNewInstance([
                    'remoteResource' => $product,
                    'localId'        => $operation->getLocalId(),
                ])
            );
        }

        $this->maybeBroadcastEvent(CatalogIntegration::class, $event);

        return new CreateOrUpdateProductResponse($remoteProductId, $product);
    }

    /**
     * Gets the remote IDs for the product from local WooCommerce mappings.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return RemoteProductIds DTO containing existing remote IDs
     */
    abstract protected function getExistingRemoteIds(CreateOrUpdateProductOperationContract $operation) : RemoteProductIds;

    /**
     * Determines if the product should be created based on existing remote IDs.
     *
     * @param RemoteProductIds $existingRemoteIds
     * @return bool
     * @throws CommerceExceptionContract
     */
    abstract protected function shouldCreate(RemoteProductIds $existingRemoteIds) : bool;

    /**
     * Executes create operations in the Commerce API.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return object
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     * @throws AdapterException
     */
    abstract protected function executeCreateOperations(CreateOrUpdateProductOperationContract $operation) : object;

    /**
     * Executes update operations in the Commerce API.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return object
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     * @throws AdapterException
     */
    abstract protected function executeUpdateOperations(CreateOrUpdateProductOperationContract $operation) : object;

    /**
     * Extracts the primary ID from the response object.
     *
     * @param object $response
     * @return non-empty-string
     * @throws MissingProductRemoteIdException
     */
    abstract protected function extractPrimaryId(object $response) : string;

    /**
     * Converts the response object to a ProductBase instance.
     *
     * @param object $response
     * @return ProductBase
     * @throws AdapterException
     */
    abstract protected function convertResponseToProductBase(object $response) : ProductBase;
}
