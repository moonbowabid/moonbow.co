<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\HandleLocalProductDeletedContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;

/**
 * Handles local product deletion for V1 implementation.
 */
class LocalProductDeletedHandler implements HandleLocalProductDeletedContract
{
    protected ProductMapRepository $productMapRepository;

    /**
     * Constructor.
     *
     * @param ProductMapRepository $productMapRepository
     */
    public function __construct(ProductMapRepository $productMapRepository)
    {
        $this->productMapRepository = $productMapRepository;
    }

    /** {@inheritDoc} */
    public function handle(int $localId) : void
    {
        $this->productMapRepository->deleteByLocalId($localId);
    }
}
