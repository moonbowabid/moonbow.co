<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers\CleanupReceivedWebhooksHandler;

/**
 * Wires the recurring received-webhooks cleanup chain into Action Scheduler.
 *
 * On every wp-admin page load, schedules a single cleanup action if none is queued. The handler reschedules itself
 * after each run, so this `admin_init` hook only fires when the chain has been broken (e.g. the action ran and the
 * reschedule failed, or the queue was cleared manually).
 */
class CleanupReceivedWebhooksInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeScheduleCleanup'])
            ->execute();

        Register::action()
            ->setGroup(CleanupReceivedWebhooksHandler::ACTION_NAME)
            ->setHandler([CleanupReceivedWebhooksHandler::class, 'handle'])
            ->execute();
    }

    /**
     * Schedules a cleanup run on the next AS dispatch when no future cleanup action is queued.
     */
    public function maybeScheduleCleanup() : void
    {
        if (as_next_scheduled_action(CleanupReceivedWebhooksHandler::ACTION_NAME)) {
            return;
        }

        $group = TypeHelper::string(Configuration::get('webhooks.receivedWebhooks.cleanupActionGroup'), 'mwc_webhook_cleanup');

        try {
            Schedule::singleAction()
                ->setName(CleanupReceivedWebhooksHandler::ACTION_NAME)
                ->setScheduleAt(new DateTime('now'))
                ->setCollection($group)
                ->setUniqueByName(true)
                ->schedule();
        } catch (InvalidScheduleException|Exception $exception) {
            SentryException::getNewInstance('Failed to schedule received-webhooks cleanup chain.', $exception);
        }
    }
}
