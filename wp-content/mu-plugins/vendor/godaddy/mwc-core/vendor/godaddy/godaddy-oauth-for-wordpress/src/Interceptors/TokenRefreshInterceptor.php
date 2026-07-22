<?php

namespace GoDaddy\WordPress\OAuth\Interceptors;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\OAuth\Interceptors\Handlers\TokenRefreshHandler;

/**
 * Interceptor for automatic token refresh.
 *
 * Registers two hooks:
 * 1. admin_init - Ensures the refresh job is scheduled
 * 2. gd_oauth_token_refresh - Handles when the job fires
 *
 * Scheduling logic lives in this class (not a separate scheduler) following
 * the mwc-core pattern (see PollingSupervisor).
 */
class TokenRefreshInterceptor extends AbstractInterceptor
{
    /** @var string Action Scheduler hook name */
    public const ACTION_NAME = 'gd_oauth_token_refresh';

    /** @var string Action Scheduler group name */
    public const ACTION_GROUP = 'godaddy-oauth';

    /** @var int Default refresh interval in seconds (45 minutes) */
    public const DEFAULT_INTERVAL = 2700;

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    public function addHooks() : void
    {
        // Schedule the recurring job (runs on admin pages at admin_init)
        add_action('admin_init', [$this, 'maybeSchedule']);

        // Handle when the scheduled action fires
        add_action(self::ACTION_NAME, [TokenRefreshHandler::class, 'handle']);
    }

    /**
     * Schedule the job if not already scheduled.
     *
     * Called on admin_init to ensure the refresh job is running.
     * Safe to call multiple times - checks if already scheduled first.
     * Catches all exceptions to prevent breaking admin page loads.
     *
     * @return void
     */
    public function maybeSchedule() : void
    {
        try {
            if (! $this->isActionSchedulerAvailable()) {
                return;
            }

            $action = Schedule::recurringAction()
                ->setName(self::ACTION_NAME)
                ->setCollection(self::ACTION_GROUP);

            if ($action->isScheduled()) {
                return;
            }

            $action
                ->setScheduleAt(new DateTime())
                ->setInterval(new DateInterval('PT'.self::getInterval().'S'))
                ->schedule();
        } catch (Exception $e) {
            // Scheduling is best-effort - never break admin pages.
            // On-demand refresh via TokenService handles the failure case.
        }
    }

    /**
     * Unschedule all instances of the job.
     *
     * @return void
     * @throws InvalidScheduleException
     */
    public static function unschedule() : void
    {
        Schedule::recurringAction()
            ->setName(self::ACTION_NAME)
            ->unschedule(true);
    }

    /**
     * Get the refresh interval in seconds.
     *
     * Configurable via GODADDY_OAUTH_REFRESH_INTERVAL constant.
     *
     * @return int Interval in seconds
     */
    public static function getInterval() : int
    {
        if (defined('GODADDY_OAUTH_REFRESH_INTERVAL') && is_numeric(GODADDY_OAUTH_REFRESH_INTERVAL)) {
            return (int) GODADDY_OAUTH_REFRESH_INTERVAL;
        }

        return self::DEFAULT_INTERVAL;
    }

    /**
     * Check if Action Scheduler is available.
     *
     * @return bool
     */
    protected function isActionSchedulerAvailable() : bool
    {
        return function_exists('as_schedule_recurring_action');
    }
}
