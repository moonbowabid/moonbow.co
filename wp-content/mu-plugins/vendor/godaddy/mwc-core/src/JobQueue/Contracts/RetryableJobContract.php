<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Contracts;

/**
 * A job that can be retried on failure.
 */
interface RetryableJobContract extends QueueableJobContract
{
    /**
     * Gets the maximum number of attempts allowed.
     */
    public function getMaxAttempts() : int;

    /**
     * Gets the current attempt count.
     */
    public function getAttemptCount() : int;

    /**
     * Sets the current attempt count.
     *
     * @return $this
     */
    public function setAttemptCount(int $count) : RetryableJobContract;
}
