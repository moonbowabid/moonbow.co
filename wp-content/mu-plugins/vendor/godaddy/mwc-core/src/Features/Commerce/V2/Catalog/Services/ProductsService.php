<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\PatchProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductBySkuOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanBroadcastResourceEventsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Factories\ProductTypeHandlerFactory;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WP_Post;

/**
 * Handles communication between Managed WooCommerce and the Commerce Catalog v2 API for product ("SkuGroup" and "SKU") CRUD operations.
 */
class ProductsService implements ProductsServiceContract
{
    use CanBroadcastResourceEventsTrait;

    /** @var CommerceContextContract context of the current site - contains the store ID */
    protected CommerceContextContract $commerceContext;

    /** @var ProductTypeHandlerFactory factory for creating product type handlers */
    protected ProductTypeHandlerFactory $handlerFactory;

    final public function __construct(
        CommerceContextContract $commerceContext,
        ProductTypeHandlerFactory $handlerFactory
    ) {
        $this->commerceContext = $commerceContext;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * {@inheritDoc}
     * @throws CommerceException|MissingProductLocalIdException|ContainerException|EntryNotFoundException
     */
    public function createOrUpdateProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        $localId = $operation->getLocalId();

        if (! $localId) {
            throw new MissingProductLocalIdException('The product has no local ID.');
        }

        // Determine the WooCommerce product type and delegate to appropriate handler
        $handler = $this->handlerFactory->getHandlerForOperation($operation);

        return $handler->createOrUpdate($operation);
    }

    /**
     * {@inheritDoc}
     * @throws CommerceException
     * @throws ContainerException
     * @throws EntryNotFoundException
     */
    public function createProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        // Legacy method that delegates to the new product-type-based approach.
        // This maintains backward compatibility with the v1 interface while using
        // the correct v2 logic based on WooCommerce product type.

        $handler = $this->handlerFactory->getHandlerForOperation($operation);

        return $handler->create($operation);
    }

    /**
     * {@inheritDoc}
     * @throws CommerceException
     */
    public function readProduct(ReadProductOperationContract $operation) : ReadProductResponseContract
    {
        throw new CommerceException('Not implemented yet.');
    }

    /**
     * {@inheritDoc}
     * @throws CommerceException
     */
    public function readProductBySku(ReadProductBySkuOperationContract $operation) : ReadProductResponseContract
    {
        throw new CommerceException('Not implemented yet.');
    }

    /**
     * {@inheritDoc}
     */
    public function listProducts(ListProductsOperationContract $operation) : ListProductsResponseContract
    {
        throw new CommerceException('Not implemented yet.');
    }

    /**
     * {@inheritDoc}
     */
    public function listProductsByLocalIds(array $localIds) : ListProductsResponseContract
    {
        throw new CommerceException('Not implemented yet.');
    }

    /**
     * {@inheritDoc}
     * @throws CommerceException|ContainerException|EntryNotFoundException
     */
    public function updateProduct(CreateOrUpdateProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract
    {
        // Legacy method that delegates to the new product-type-based approach.
        // The $remoteId parameter is no longer passed to handlers since each handler
        // determines its own remote IDs based on product type requirements.

        $handler = $this->handlerFactory->getHandlerForOperation($operation);

        return $handler->update($operation);
    }

    /**
     * {@inheritDoc}
     */
    public function updateProductFromWpPost(WP_Post $productPost, callable $operationCallback) : void
    {
        $sourceProduct = ProductsRepository::get($productPost->ID);
        if (! $sourceProduct) {
            throw new CommerceException(sprintf('Unable to update product from WP_Post, failed to fetch product %d.', $productPost->ID));
        }

        $nativeProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();

        /** @var CreateOrUpdateProductOperationContract $operation */
        $operation = $operationCallback($nativeProduct);

        // V2 uses handler-based approach instead of single remote ID
        $handler = $this->handlerFactory->getHandlerForOperation($operation);
        $handler->update($operation);
    }

    /**
     * {@inheritDoc}
     */
    public function patchProduct(PatchProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract
    {
        throw new CommerceException('Not implemented yet.');
    }
}
