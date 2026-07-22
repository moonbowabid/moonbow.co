<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute as CommonAttribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\HasProductAttributeMappingServiceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\AttributesAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Includes logic to convert local {@see CommonAttribute} objects into Commerce {@see Attribute} objects.
 */
trait CanConvertProductAttributesTrait
{
    use HasProductAttributeMappingServiceTrait;

    /**
     * Determines whether the provided product should have its attributes converted
     * into the v2 equivalent. If this returns false then it means the local attributes
     * do not properly convert into v2 expectations. For example: a local "simple" product
     * that has attributes should not have its attributes written to the remote service.
     *
     * @param Product $product
     * @return bool
     */
    protected function shouldHaveRemoteAttributes(Product $product) : bool
    {
        return $product->getType() === 'variable';
    }

    /**
     * Converts local product attributes into an array of Commerce Attribute objects.
     *
     * Use {@see setProductAttributeMappingService()} to set the attribute mapping necessary to
     * translate between local attribute keys and remote attribute names.
     *
     * @return Attribute[]
     */
    protected function convertProductAttributes(Product $product) : array
    {
        if (! $this->shouldHaveRemoteAttributes($product)) {
            return [];
        }

        return $this->convertAttributes((array) $product->getAttributes());
    }

    /**
     * Converts the given list of product attributes into an array of Commerce Attribute objects.
     *
     * Use {@see setProductAttributeMappingService()} to set the attribute mapping necessary to
     * translate between local attribute keys and remote attribute names.
     *
     * @param CommonAttribute[] $attributes
     * @return Attribute[]
     */
    protected function convertAttributes(array $attributes) : array
    {
        return AttributesAdapter::getNewInstance($attributes)
            ->setProductAttributeMappingService($this->getProductAttributeMappingService())
            ->convertFromSource();
    }

    /**
     * Converts local product attributes into an array of Commerce Attribute objects.
     *
     * If a callback is provided, it will be used to select which attributes are converted.
     *
     * Use {@see setProductAttributeMappingService()} to set the attribute mapping necessary to
     * translate between local attribute keys and remote attribute names.
     *
     * @return Attribute[]
     */
    protected function convertProductAttributesWhere(Product $product, ?callable $callback = null) : array
    {
        if (! $this->shouldHaveRemoteAttributes($product)) {
            return [];
        }

        $attributes = array_values(array_filter((array) $product->getAttributes(), $callback));

        return $this->convertAttributes($attributes);
    }
}
