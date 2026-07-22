<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Factories;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Contracts\ProductTypeHandlerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers\SimpleProductHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers\VariableProductHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Handlers\VariationProductHandler;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Factory for creating product type handlers with container-based dependency injection.
 *
 * Uses lazy loading to only instantiate handlers and their dependencies when needed.
 */
class ProductTypeHandlerFactory
{
    /** @var ProductTypeHandlerContract[] */
    protected array $handlerInstances = [];

    /** @var array<string, class-string<ProductTypeHandlerContract>> Map of product types to their handler classes */
    protected array $handlerTypes = [
        'simple'    => SimpleProductHandler::class,
        'variable'  => VariableProductHandler::class,
        'variation' => VariationProductHandler::class,
    ];

    /**
     * Get handler for the specified product type.
     *
     * @param Product $product
     * @return ProductTypeHandlerContract
     * @throws CommerceException
     * @throws ContainerException
     * @throws EntryNotFoundException
     */
    public function getHandler(Product $product) : ProductTypeHandlerContract
    {
        $productType = $product->getType();

        if (! $productType) {
            throw new CommerceException('Product type is not set.');
        }

        if (! isset($this->handlerInstances[$productType])) {
            $this->handlerInstances[$productType] = $this->createHandler($productType);
        }

        return $this->handlerInstances[$productType];
    }

    /**
     * Get handler for an operation - determines product type automatically.
     *
     * @throws CommerceException|ContainerException|EntryNotFoundException
     */
    public function getHandlerForOperation(CreateOrUpdateProductOperationContract $operation) : ProductTypeHandlerContract
    {
        return $this->getHandler($operation->getProduct());
    }

    /**
     * Create a new handler instance for the product type using container resolution.
     *
     * @throws CommerceException
     * @throws ContainerException
     * @throws EntryNotFoundException
     */
    protected function createHandler(string $productType) : ProductTypeHandlerContract
    {
        if (! isset($this->handlerTypes[$productType])) {
            throw new CommerceException("Unsupported product type: {$productType}");
        }

        $instance = ContainerFactory::getInstance()->getSharedContainer()->get($this->handlerTypes[$productType]);

        if ($instance instanceof ProductTypeHandlerContract) {
            return $instance;
        }

        /* @phpstan-ignore-next-line phpstan sees this as unreachable code but it's here in case an invalid handler is registered */
        throw new CommerceException('Invalid handler instance type: '.get_class($instance));
    }
}
