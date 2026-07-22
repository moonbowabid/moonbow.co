<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\BeforeLoadProductInformationEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineShouldPrimeProductsCacheTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\PrimeInventoryCacheServiceContract;

class PrimeInventoryCacheBeforeLoadingProductInformationSubscriber implements SubscriberContract
{
    use CanDetermineShouldPrimeProductsCacheTrait;

    protected PrimeInventoryCacheServiceContract $primeInventoryCacheService;

    public function __construct(PrimeInventoryCacheServiceContract $primeInventoryCacheService)
    {
        $this->primeInventoryCacheService = $primeInventoryCacheService;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof BeforeLoadProductInformationEvent) {
            return;
        }

        if (! static::shouldPrimeProductsCache()) {
            return;
        }

        $this->tryToPrimeInventoryCache($event);
    }

    protected function tryToPrimeInventoryCache(BeforeLoadProductInformationEvent $event) : void
    {
        $productIds = $event->getLocalIds();

        if (empty($productIds)) {
            return;
        }

        if ($event->getWithVariants()) {
            $productIds = array_merge($productIds, $this->getVariantIds($productIds));
        }

        $this->primeInventoryCacheService->primeByLocalProductIds($productIds);
    }

    /**
     * Gets the local IDs of the variants for the supplied local parent IDs.
     *
     * @param int[] $localParentIds array of local parent product IDs
     * @return int[] array of local variant product IDs
     */
    protected function getVariantIds(array $localParentIds) : array
    {
        $variantIds = CatalogIntegration::withoutReads(function () use ($localParentIds) {
            return get_posts([
                'post_parent__in' => $localParentIds,
                'post_type'       => CatalogIntegration::PRODUCT_VARIATION_POST_TYPE,
                'fields'          => 'ids',
                'post_status'     => ['publish', 'private'],
                'numberposts'     => -1,
            ]);
        });

        return TypeHelper::arrayOfIntegers($variantIds);
    }
}
