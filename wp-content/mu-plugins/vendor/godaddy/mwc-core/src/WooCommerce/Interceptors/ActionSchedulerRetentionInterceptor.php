<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Traits\ShouldLoadOnlyIfWooCommerceIsEnabledTrait;

/**
 * Lowers the Action Scheduler retention period so completed and canceled actions (and their log rows) are pruned sooner.
 */
class ActionSchedulerRetentionInterceptor extends AbstractInterceptor
{
    use ShouldLoadOnlyIfWooCommerceIsEnabledTrait;

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('action_scheduler_retention_period')
            ->setHandler([$this, 'getActionSchedulerRetentionPeriod'])
            ->execute();
    }

    /**
     * Returns the Action Scheduler retention period in seconds.
     */
    public function getActionSchedulerRetentionPeriod() : int
    {
        $default = TypeHelper::int(Configuration::get('reporting.actionScheduler.retentionDays.default'), 7);
        $days = TypeHelper::int(Configuration::get('reporting.actionScheduler.retentionDays.override'), $default);

        return $days * DAY_IN_SECONDS;
    }
}
