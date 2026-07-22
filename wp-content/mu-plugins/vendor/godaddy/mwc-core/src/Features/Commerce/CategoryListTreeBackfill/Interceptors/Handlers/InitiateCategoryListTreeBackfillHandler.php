<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Jobs\CategoryListTreeBackfillJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Jobs\CategoryListTreeMappingJob;
use GoDaddy\WordPress\MWC\Core\JobQueue\JobQueue;

/**
 * Handles the callback for the category list tree backfill job.
 */
class InitiateCategoryListTreeBackfillHandler extends AbstractInterceptorHandler
{
    /**
     * Dispatches the category list tree backfill job.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        try {
            JobQueue::getNewInstance()->chain([
                CategoryListTreeMappingJob::class,
                CategoryListTreeBackfillJob::class,
            ])->dispatch();
        } catch (Exception $exception) {
            SentryException::getNewInstance('Failed to dispatch category list tree backfill job.', $exception);
        }
    }
}
