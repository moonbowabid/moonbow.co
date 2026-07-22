<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Interceptors;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\Helpers\EligibleApiVersionHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Interceptors\Handlers\InitiateCategoryListTreeBackfillHandler;

/**
 * Interceptor to handle scheduling the category list tree backfill job.
 */
class InitiateCategoryListTreeBackfillInterceptor extends AbstractInterceptor
{
    public const JOB_NAME = 'mwc_gd_commerce_category_list_tree_backfill';

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
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeScheduleJob'])
            ->execute();

        Register::action()
            ->setGroup(TypeHelper::string(static::JOB_NAME, ''))
            ->setHandler([InitiateCategoryListTreeBackfillHandler::class, 'handle'])
            ->execute();
    }

    /**
     * Schedules the backfill job if prerequisites are met and it's not already scheduled.
     */
    public function maybeScheduleJob() : void
    {
        if (! $this->shouldSchedule()) {
            return;
        }

        $job = Schedule::singleAction()->setName(TypeHelper::string(static::JOB_NAME, ''));

        if (! $job->isScheduled()) {
            try {
                $job
                    ->setScheduleAt($this->getScheduledAtWithRandomDelay(new DateTime('now')))
                    ->schedule();
            } catch (Exception $exception) {
                SentryException::getNewInstance('Failed to schedule category list tree backfill job.', $exception);
            }
        }
    }

    /**
     * Determines whether the job should be scheduled.
     */
    protected function shouldSchedule() : bool
    {
        if (! $this->eligibleApiVersionHelper->isEligibleForV2()) {
            return false;
        }

        if (empty(get_option('mwc_v2_category_mapping_completed_at'))) {
            return false;
        }

        if (! empty(get_option('mwc_v2_category_list_tree_backfill_completed_at'))) {
            return false;
        }

        return true;
    }

    /**
     * Gets the scheduled at time with a random delay (jitter) applied.
     */
    protected function getScheduledAtWithRandomDelay(DateTime $scheduledAt) : DateTime
    {
        $jitter = $this->jitterProvider->getJitter(59 * 60);

        return $scheduledAt->modify("+{$jitter} seconds") ?: $scheduledAt;
    }
}
