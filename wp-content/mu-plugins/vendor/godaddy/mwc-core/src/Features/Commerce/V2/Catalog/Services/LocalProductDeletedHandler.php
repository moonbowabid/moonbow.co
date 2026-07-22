<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\HandleLocalProductDeletedContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuGroupMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;

/**
 * Handles local product deletion for V2 implementation.
 */
class LocalProductDeletedHandler implements HandleLocalProductDeletedContract
{
    protected SkuGroupMapRepository $skuGroupMapRepository;
    protected SkuMapRepository $skuMapRepository;

    /**
     * Constructor.
     *
     * @param SkuGroupMapRepository $skuGroupMapRepository
     * @param SkuMapRepository $skuMapRepository
     */
    public function __construct(SkuGroupMapRepository $skuGroupMapRepository, SkuMapRepository $skuMapRepository)
    {
        $this->skuGroupMapRepository = $skuGroupMapRepository;
        $this->skuMapRepository = $skuMapRepository;
    }

    /** {@inheritDoc} */
    public function handle(int $localId) : void
    {
        $this->skuGroupMapRepository->deleteByLocalId($localId);
        $this->skuMapRepository->deleteByLocalId($localId);
    }
}
