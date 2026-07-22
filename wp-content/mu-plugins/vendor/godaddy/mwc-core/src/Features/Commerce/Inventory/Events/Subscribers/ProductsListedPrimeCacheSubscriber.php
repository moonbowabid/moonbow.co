<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsListedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\PrimeInventoryCacheServiceContract;

class ProductsListedPrimeCacheSubscriber implements SubscriberContract
{
    protected PrimeInventoryCacheServiceContract $cachePrimingService;

    public function __construct(PrimeInventoryCacheServiceContract $cachePrimingService)
    {
        $this->cachePrimingService = $cachePrimingService;
    }

    /**
     * {@inheritDoc}
     *
     * @param ProductsListedEvent $event
     */
    public function handle(EventContract $event) : void
    {
        if (! $this->isValid($event) || ! $this->shouldHandle()) {
            return;
        }

        if (! $productIds = $this->getProductIds($event->productAssociations)) {
            return;
        }

        $this->cachePrimingService->primeByRemoteProductIds($productIds);
    }

    /**
     * Valid events are {@see ProductsListedEvent} where the productAssociations array is not empty.
     *
     * @param EventContract $event
     * @return bool
     */
    public function isValid(EventContract $event) : bool
    {
        return $event instanceof ProductsListedEvent && ! empty($event->productAssociations);
    }

    /**
     * Events should only be handled if the inventory integration is enabled with read capability.
     *
     * @return bool
     */
    public function shouldHandle() : bool
    {
        return InventoryIntegration::shouldLoad()
            && InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * @param ProductAssociation[] $productAssociations
     *
     * @return string[]
     */
    protected function getProductIds(array $productAssociations) : array
    {
        return array_values(
            array_filter(
                array_map(
                    static function (ProductAssociation $assoc) : ?string {
                        $inventory = $assoc->remoteResource->inventory;

                        // only attempt to prime caches for products that are actively using the inventory service
                        if ($inventory && $inventory->externalService && $inventory->tracking) {
                            return $assoc->remoteResource->productId;
                        }

                        return null;
                    },
                    $productAssociations
                )
            )
        );
    }
}
