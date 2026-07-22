<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\JobQueue\Interceptors\QueuedJobInterceptor;

/**
 * Exception thrown when the handling (processing) of a queued job fails (throws an exception).
 * {@see QueuedJobInterceptor::handleJob()}.
 */
class QueuedJobHandlingFailedException extends SentryException
{
}
