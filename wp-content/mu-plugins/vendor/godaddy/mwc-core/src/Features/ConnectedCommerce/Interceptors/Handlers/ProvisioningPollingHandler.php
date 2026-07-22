<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\Handlers;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\CheckProvisioningInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\Contracts\ProvisioningServiceContract;

/**
 * Ensures a provisioning check action is scheduled while provisioning is pending.
 */
class ProvisioningPollingHandler extends AbstractInterceptorHandler
{
    protected ProvisioningServiceContract $provisioningService;

    public function __construct(ProvisioningServiceContract $provisioningService)
    {
        $this->provisioningService = $provisioningService;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        if (! in_array($this->provisioningService->getProvisioningStatus(), ['PENDING', 'IN_PROGRESS'], true)) {
            return;
        }

        $this->maybeSchedulePolling();
    }

    /**
     * Schedules a single polling action if not already scheduled.
     */
    protected function maybeSchedulePolling() : void
    {
        $pollingJob = Schedule::singleAction()
            ->setName(CheckProvisioningInterceptor::JOB_NAME);

        if ($pollingJob->isScheduled()) {
            return;
        }

        try {
            $pollingJob
                ->setUniqueByName()
                ->setScheduleAt((new DateTime('now'))->add(new DateInterval('PT1M')))
                ->schedule();
        } catch (Exception $exception) {
            SentryException::getNewInstance('Failed to schedule Connected Commerce provisioning polling job.', $exception);
        }
    }
}
