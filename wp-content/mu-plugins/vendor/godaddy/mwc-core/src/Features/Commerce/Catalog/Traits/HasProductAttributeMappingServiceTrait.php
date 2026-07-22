<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductAttributeMappingService;

trait HasProductAttributeMappingServiceTrait
{
    protected ?ProductAttributeMappingService $productAttributeMappingService = null;

    /**
     * @return $this
     */
    public function setProductAttributeMappingService(ProductAttributeMappingService $productAttributeMappingService)
    {
        $this->productAttributeMappingService = $productAttributeMappingService;

        return $this;
    }

    public function getProductAttributeMappingService() : ProductAttributeMappingService
    {
        return $this->productAttributeMappingService ??= new ProductAttributeMappingService();
    }
}
