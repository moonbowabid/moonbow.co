<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;

/**
 * Contract for handling product-type-specific operations.
 */
interface ProductTypeHandlerContract
{
    /**
     * Handle create or update operation for this product type.
     */
    public function createOrUpdate(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;

    /**
     * Handle create operation for this product type.
     */
    public function create(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;

    /**
     * Handle update operation for this product type.
     */
    public function update(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;
}
