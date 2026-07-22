<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\Helpers\EligibleApiVersionHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\CommerceCatalogV2Mapping;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors\Handler\InitiateMappingHandler;

/**
 * Interceptor to handle scheduling the category mapping job.
 *
 * This class is responsible for kicking off individual category mapping processes.
 */
class InitiateMappingInterceptor extends AbstractInterceptor
{
    public const MAPPING_JOB_NAME = 'mwc_gd_commerce_v2_mapping_manager';

    protected EligibleApiVersionHelper $eligibleApiVersionHelper;

    protected CanGetJitterContract $jitterProvider;

    public function __construct(
        EligibleApiVersionHelper $eligibleApiVersionHelper,
        CanGetJitterContract $jitterProvider
    ) {
        $this->eligibleApiVersionHelper = $eligibleApiVersionHelper;
        $this->jitterProvider = $jitterProvider;
    }

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeScheduleJob'])
            ->execute();

        Register::action()
            ->setGroup(TypeHelper::string(static::MAPPING_JOB_NAME, ''))
            ->setHandler([InitiateMappingHandler::class, 'handle'])
            ->execute();
    }

    /**
     * Schedules the mapping job if it's not already scheduled.
     *
     * @return void
     */
    public function maybeScheduleJob() : void
    {
        // only schedule the job if the store is eligible for v2 and mapping is not yet complete
        if (! $this->eligibleApiVersionHelper->isEligibleForV2() || CommerceCatalogV2Mapping::hasCompletedMappingJobs()) {
            return;
        }

        $job = Schedule::singleAction()->setName(TypeHelper::string(static::MAPPING_JOB_NAME, ''));

        if (! $job->isScheduled()) {
            try {
                $job
                    ->setScheduleAt($this->getScheduledAtWithRandomDelay(new DateTime('now')))
                    ->schedule();
            } catch(Exception $exception) {
                SentryException::getNewInstance('Failed to schedule Commerce V2 category mapping job.', $exception);
            }
        }
    }

    /**
     * Gets the scheduled at time with a random delay (jitter) applied.
     *
     * @param DateTime $scheduledAt
     * @return DateTime
     */
    protected function getScheduledAtWithRandomDelay(DateTime $scheduledAt) : DateTime
    {
        $jitter = $this->jitterProvider->getJitter(59 * 60);

        return $scheduledAt->modify("+{$jitter} seconds") ?: $scheduledAt;
    }
}
