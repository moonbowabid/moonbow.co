<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductParentIdResolverContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\ProductMapRepositoryContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * V1 implementation for resolving parent product ID conversions.
 */
class ProductParentIdResolver implements ProductParentIdResolverContract
{
    /** @var ProductMapRepositoryContract */
    protected ProductMapRepositoryContract $productMapRepository;

    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productsMappingService;

    /**
     * Constructor.
     *
     * @param ProductMapRepositoryContract $productMapRepository
     * @param ProductsMappingServiceContract $productsMappingService
     */
    public function __construct(
        ProductMapRepositoryContract $productMapRepository,
        ProductsMappingServiceContract $productsMappingService
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->productsMappingService = $productsMappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalParentId(string $remoteParentId) : int
    {
        $localParentId = $this->productMapRepository->getLocalId($remoteParentId);

        if (empty($localParentId)) {
            throw new MissingProductLocalIdForParentException("Failed to retrieve local ID for parent product {$remoteParentId}.");
        }

        return TypeHelper::int($localParentId, 0);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteParentId(int $localParentId) : string
    {
        $remoteParentId = $this->productsMappingService->getRemoteId(Product::getNewInstance()->setId($localParentId));

        if (! $remoteParentId) {
            // throwing an exception here prevents us from incorrectly identifying the product as having no parent in Commerce
            throw new MissingProductRemoteIdForParentException("Failed to retrieve remote ID for parent product {$localParentId}.");
        }

        return $remoteParentId;
    }
}
