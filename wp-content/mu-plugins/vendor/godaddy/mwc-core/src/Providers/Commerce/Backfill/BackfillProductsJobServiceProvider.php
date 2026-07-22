<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Backfill;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductsJob as V1BackfillProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Backfill\Jobs\BackfillProductsJob as V2BackfillProductsJob;

/**
 * Service provider for BackfillProductsJob.
 *
 * Conditionally loads V1 or V2 version based on CatalogIntegration::shouldUseV2Api().
 */
class BackfillProductsJobServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [BackfillProductsJob::class];

    /**
     * Register the service.
     */
    public function register() : void
    {
        $backfillJobConcrete = CatalogIntegration::shouldUseV2Api()
            ? V2BackfillProductsJob::class
            : V1BackfillProductsJob::class;

        $this->getContainer()->singleton(BackfillProductsJob::class, $backfillJobConcrete);
    }
}
