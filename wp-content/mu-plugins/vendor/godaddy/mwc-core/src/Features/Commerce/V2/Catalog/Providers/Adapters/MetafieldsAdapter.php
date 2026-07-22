<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\Traits\CanMapDimensionUnitsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Metafield;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter to convert Product metadata to Commerce platform metafields format.
 */
class MetafieldsAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;
    use CanMapDimensionUnitsTrait;

    /** @var Product */
    protected Product $source;

    /**
     * Constructor.
     *
     * @param Product $source
     */
    public function __construct(Product $source)
    {
        $this->source = $source;
    }

    /**
     * Convert Product metadata to Commerce platform metafields format.
     *
     * @return Metafield[]
     */
    public function convertFromSource() : array
    {
        $metafields = [];

        // Brand (canonical - always write)
        $metafields[] = new Metafield([
            'namespace' => 'commerce-apps',
            'key'       => 'brand',
            'value'     => TypeHelper::string($this->source->getMarketplacesBrand(), ''),
            'type'      => 'string',
        ]);

        // Tax Category (canonical - always write)
        $metafields[] = new Metafield([
            'namespace' => 'commerce-apps',
            'key'       => 'taxCategory',
            'value'     => TypeHelper::string($this->source->getTaxCategory(), 'standard') ?: 'standard',
            'type'      => 'string',
        ]);

        // Low Inventory Threshold (canonical - always write)
        $lowStockAmount = $this->source->getLowStockThreshold();
        $metafields[] = new Metafield([
            'namespace' => 'commerce-apps',
            'key'       => 'lowInventoryThreshold',
            'value'     => $lowStockAmount !== null ? (string) $lowStockAmount : '',
            'type'      => 'number',
        ]);

        // Dimensions (canonical - always write). Unrecognised units fall back to 'in' so the product still syncs.
        $dimensions = $this->source->getDimensions();
        $unit = $this->mapDimensionUnitToSku($dimensions->getUnitOfMeasurement()) ?? 'in';

        $dimensionsValue = json_encode([
            'length' => $dimensions->getLength(),
            'width'  => $dimensions->getWidth(),
            'height' => $dimensions->getHeight(),
            'unit'   => $unit,
        ]);

        $metafields[] = new Metafield([
            'namespace' => 'commerce-apps',
            'key'       => 'shippingWeightAndDimensions.dimensions',
            'value'     => $dimensionsValue ?: '',
            'type'      => 'json',
        ]);

        return $metafields;
    }

    /**
     * @return array<string, mixed>
     */
    public function convertToSource() : array
    {
        // no-op, metafields are written to {@see ProductBase} in {@CanConvertSkuToProductBaseTrait} for use in products.
        return [];
    }
}
