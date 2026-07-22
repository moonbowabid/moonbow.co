<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductParentIdResolverContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuGroupMapRepository;

/**
 * V2 implementation for resolving parent product ID conversions.
 *
 * In this scenario parent mappings are stored in the SkuGroup repository.
 */
class ProductParentIdResolver implements ProductParentIdResolverContract
{
    protected SkuGroupMapRepository $skuGroupMapRepository;

    public function __construct(SkuGroupMapRepository $skuGroupMapRepository)
    {
        $this->skuGroupMapRepository = $skuGroupMapRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalParentId(string $remoteParentId) : int
    {
        $localParentId = $this->skuGroupMapRepository->getLocalId($remoteParentId);

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
        $remoteParentId = $this->skuGroupMapRepository->getRemoteId($localParentId);

        if (! $remoteParentId) {
            // throwing an exception here prevents us from incorrectly identifying the product as having no parent in Commerce
            throw new MissingProductRemoteIdForParentException("Failed to retrieve remote ID for parent product {$localParentId}.");
        }

        return $remoteParentId;
    }
}
