<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors\Handler\SyncProductMetadataHandler;

/**
 * Exceptions thrown when the {@see SyncProductMetadataHandler} fails.
 */
class SyncProductMetadataFailedException extends SentryException
{
}
