<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute as CommonAttribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductAttributeMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductAttributesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductMediaObjectsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanConvertProductTimestampsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanMapProductStatusTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter to convert Product model to SKU Group data object.
 *
 * Maps Product model properties to SKU Group fields:
 *  - name, label, description, htmlDescription
 *  - type (PHYSICAL/DIGITAL based on virtual/downloadable status)
 *  - status (based on product status)
 *  - attributes (WooCommerce product attributes converted to V2 format)
 */
class ProductToSkuGroupAdapter
{
    use CanConvertProductMediaObjectsTrait;
    use CanConvertProductTimestampsTrait;
    use CanConvertProductAttributesTrait;
    use CanMapProductStatusTrait;

    /**
     * Converts a core WooCommerce {@see Product} model to a {@see SkuGroup} data object.
     *
     * @param Product $product
     * @param string|null $remoteId
     * @return SkuGroup
     */
    public function convert(Product $product, ?string $remoteId = null) : SkuGroup
    {
        $data = [
            'name'            => $product->getSlug() ?? '',
            'label'           => $product->getName() ?? '',
            'description'     => wp_strip_all_tags($product->getDescription()),
            'htmlDescription' => $product->getDescription(),
            'status'          => $this->mapProductStatus($product->getStatus()),
            'type'            => $this->mapProductType($product),
            'createdAt'       => $this->formatDateTimeForApi($product->getCreatedAt()),
            'updatedAt'       => $this->formatDateTimeForApi($product->getUpdatedAt()),
            'archivedAt'      => $this->getArchivedAt($product),
            'mediaObjects'    => $this->convertProductMediaObjects($product),
            'channels'        => $this->getChannels(),
            'attributes'      => $this->convertProductVariationAttributes($product),
        ];

        if ($remoteId) {
            $data['id'] = $remoteId;
        }

        return new SkuGroup($data);
    }

    /**
     * Map Product type to SKU Group type.
     *
     * @param Product $product
     * @return string
     */
    protected function mapProductType(Product $product) : string
    {
        // Digital products are virtual or downloadable
        if ($product->isVirtual() || $product->isDownloadable()) {
            return 'DIGITAL';
        }

        // All others are physical products
        return 'PHYSICAL';
    }

    /**
     * @return Channel[]
     */
    protected function getChannels() : array
    {
        return [new Channel(['channelId' => Commerce::getChannelId()])];
    }

    /**
     * Converts only the product attributes that are used for variations.
     *
     * Filters out regular product attributes that are not used to define product variations.
     * Only variation attributes should be included in the SKU Group attributes.
     *
     * @param Product $product
     * @return Attribute[] Commerce attributes array containing only variation attributes
     */
    protected function convertProductVariationAttributes(Product $product) : array
    {
        return $this->setProductAttributeMappingService(ProductAttributeMappingService::for($product))
            ->convertProductAttributesWhere($product, fn (CommonAttribute $attribute) => $attribute->isForVariant());
    }
}
