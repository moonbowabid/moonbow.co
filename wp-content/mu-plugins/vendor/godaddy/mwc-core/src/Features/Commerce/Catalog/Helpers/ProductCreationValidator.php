<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotCreatableException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;

/**
 * Helper class to determine if we should create a local product in the remote Commerce API.
 * See also {@see HasProductPlatformDataStoreCrudTrait::shouldWriteProductToCatalog()} which handles more generic
 *  "should we write this at all" conditions. Whereas this check is specific to _creating_ products only.
 */
class ProductCreationValidator
{
    /**
     * Validates if a product should be created in the remote Commerce API.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return void
     * @throws MissingProductLocalIdException|ProductNotCreatableException|Exception
     */
    public static function validateShouldCreateProduct(CreateOrUpdateProductOperationContract $operation) : void
    {
        $product = $operation->getProduct();

        if (($parentId = $product->getParentId()) && static::isProductDraft($parentId)) {
            throw new ProductNotCreatableException('The parent product is not published.');
        }

        if ($product->isDraft()) {
            throw new ProductNotCreatableException('Draft products cannot be created in the platform.');
        }
    }

    /**
     * Determines if a product is a draft in WooCommerce.
     *
     * @param int $productId
     * @return bool
     * @throws MissingProductLocalIdException|Exception
     */
    protected static function isProductDraft(int $productId) : bool
    {
        if (empty($wcProduct = ProductsRepository::get($productId))) {
            throw new MissingProductLocalIdException('Unable to find parent product.');
        }

        return ProductAdapter::getNewInstance($wcProduct)->convertFromSource()->isDraft();
    }
}
