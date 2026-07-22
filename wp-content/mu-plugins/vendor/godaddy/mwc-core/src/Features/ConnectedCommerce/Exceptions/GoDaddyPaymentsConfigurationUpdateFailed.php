<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * {@see SentryException} used to report when the GoDaddy Payments gateway configuration
 * could not be reconciled with the current Connected Commerce provisioning context.
 *
 * Instances are captured via {@see SentryException::getNewInstance()} rather than thrown,
 * and reported at 100% via {@see configurations/reporting.php} so we can detect every
 * misalignment between the merchant-selected store and the connected payments account.
 */
class GoDaddyPaymentsConfigurationUpdateFailed extends SentryException
{
}
