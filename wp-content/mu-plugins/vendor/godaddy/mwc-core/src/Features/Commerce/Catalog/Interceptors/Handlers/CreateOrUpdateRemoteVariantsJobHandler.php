<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CreateOrUpdateRemoteVariantsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Jobs\BatchCreateOrUpdateProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\JobQueue\JobQueue;

/**
 * Handles creating or updating variants in the remote platform.
 * {@see CreateOrUpdateRemoteVariantsInterceptor}.
 */
class CreateOrUpdateRemoteVariantsJobHandler extends AbstractInterceptorHandler
{
    protected ProductsServiceContract $productsService;

    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }

    /**
     * Identifies which local variants don't yet exist in the remote platform and creates them.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        if (! isset($args[0])) {
            return;
        }

        $variantIds = TypeHelper::arrayOfIntegers($args[0]);

        // TODO: Restore the ability to detect variant IDs to create in https://godaddy-corp.atlassian.net/browse/MWC-19186 {wvega 2026-02-18}
        $variantIdsToCreate = $variantIds;

        if (empty($variantIdsToCreate)) {
            return;
        }

        // kick-off the job queue
        JobQueue::getNewInstance()->chain([
            BatchCreateOrUpdateProductsJob::class,
        ])->dispatch($variantIdsToCreate);
    }

    /**
     * Identifies which local variants don't yet exist in the remote platform.
     *
     * @param int[] $localVariantIds
     * @return int[]
     * @throws CommerceExceptionContract|BaseException|CachingStrategyException
     */
    protected function findVariantIdsToCreate(array $localVariantIds) : array
    {
        try {
            $existingVariantIds = $this->productsService->listProductsByLocalIds($localVariantIds)->getLocalIds();
        } catch (MissingRemoteIdsAfterLocalIdConversionException $e) {
            // if we don't have any remote IDs, then we need to create all of them
            return $localVariantIds;
        }

        return array_values(array_diff($localVariantIds, $existingVariantIds));
    }
}
