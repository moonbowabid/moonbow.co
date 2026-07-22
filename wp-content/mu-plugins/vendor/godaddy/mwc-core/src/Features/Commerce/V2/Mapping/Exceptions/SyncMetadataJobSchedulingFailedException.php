<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors\SyncProductMetadataInterceptor;

/**
 * Exception thrown when the scheduling of the {@see SyncProductMetadataInterceptor::JOB_NAME} job fails.
 */
class SyncMetadataJobSchedulingFailedException extends SentryException
{
}
