<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;

class SkuRelationshipUpdateBuilder
{
    /** @var MediaUpdatesBuilder */
    protected MediaUpdatesBuilder $mediaUpdatesBuilder;

    /** @var PriceUpdatesBuilder */
    protected PriceUpdatesBuilder $priceUpdatesBuilder;

    public function __construct(
        MediaUpdatesBuilder $mediaUpdatesBuilder,
        PriceUpdatesBuilder $priceUpdatesBuilder
    ) {
        $this->mediaUpdatesBuilder = $mediaUpdatesBuilder;
        $this->priceUpdatesBuilder = $priceUpdatesBuilder;
    }

    public function build(CreateOrUpdateProductOperationContract $operation, Sku $sku) : RelationshipUpdates
    {
        $updates = new RelationshipUpdates();

        if ($mediaUpdates = $this->mediaUpdatesBuilder->build($operation, $sku)) {
            $updates->mediaUpdates = $mediaUpdates;
        }

        if ($priceUpdates = $this->priceUpdatesBuilder->build($operation, $sku)) {
            $updates->priceUpdates = $priceUpdates;
        }

        // @todo Add other relationship types (inventory, etc.)

        return $updates;
    }
}
