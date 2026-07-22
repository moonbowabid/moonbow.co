<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Action Scheduler handler that deletes a single batch of expired received-webhook rows and reschedules itself.
 *
 * Pacing is controlled by the rescheduled run time: a full batch implies more work to do, so the next run is scheduled
 * a configurable interval later (drain mode); a partial or empty batch implies the table is caught up, so the next run
 * is scheduled a day later.
 */
class CleanupReceivedWebhooksHandler
{
    public const ACTION_NAME = 'mwc_gd_cleanup_received_webhooks';

    /**
     * Runs one cleanup batch and schedules the next run.
     */
    public static function handle() : void
    {
        $retentionDays = static::getRetentionDays();
        $batchSize = TypeHelper::int(Configuration::get('webhooks.receivedWebhooks.cleanupBatchSize'), 1000);
        $intervalSeconds = TypeHelper::int(Configuration::get('webhooks.receivedWebhooks.cleanupBatchIntervalSeconds'), 60);
        $group = TypeHelper::string(Configuration::get('webhooks.receivedWebhooks.cleanupActionGroup'), 'mwc_webhook_cleanup');

        $threshold = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("-{$retentionDays} days");

        try {
            $deleted = static::getRepository()->deleteOlderThan($threshold, $batchSize);
        } catch (Exception $exception) {
            SentryException::getNewInstance('Failed to delete expired received-webhook rows.', $exception);
            $deleted = 0;
        }

        $nextRunDelay = $deleted >= $batchSize ? $intervalSeconds : DAY_IN_SECONDS;

        try {
            Schedule::singleAction()
                ->setName(self::ACTION_NAME)
                ->setScheduleAt(new DateTime('@'.(time() + $nextRunDelay)))
                ->setCollection($group)
                ->schedule();
        } catch (InvalidScheduleException|Exception $exception) {
            SentryException::getNewInstance('Failed to schedule next received-webhooks cleanup run.', $exception);
        }
    }

    /**
     * Resolves the retention threshold in days, preferring the constant override when set.
     *
     * Clamps the resolved value to at least 1 day so that a misconfigured override (e.g. `0` or a negative number,
     * which would otherwise treat all rows as expired) cannot wipe the entire table.
     */
    protected static function getRetentionDays() : int
    {
        $override = Configuration::get('webhooks.receivedWebhooks.retentionDays.override');

        $resolved = null !== $override
            ? TypeHelper::int($override, 30)
            : TypeHelper::int(Configuration::get('webhooks.receivedWebhooks.retentionDays.default'), 30);

        return max(1, $resolved);
    }

    /**
     * Builds the repository used to perform the actual delete; extracted to allow mocking in tests.
     */
    protected static function getRepository() : WebhooksRepository
    {
        return new WebhooksRepository();
    }
}
