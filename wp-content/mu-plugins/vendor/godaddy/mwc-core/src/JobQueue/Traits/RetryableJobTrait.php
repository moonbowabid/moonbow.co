<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\RetryableJobContract;

/**
 * Trait for retryable jobs. Implements common methods in {@see RetryableJobContract}.
 *
 * @phpstan-require-implements RetryableJobContract
 */
trait RetryableJobTrait
{
    use QueueableJobTrait;

    /**
     * Handles the job with retry-on-failure orchestration.
     */
    public function handle() : void
    {
        try {
            $this->executeJob();
        } catch (Exception $exception) {
            $this->handleFailedAttempt($exception);
        }

        $this->jobDone();
    }

    /**
     * Gets the current attempt count.
     */
    public function getAttemptCount() : int
    {
        return TypeHelper::int(ArrayHelper::get($this->args, '0'), 1);
    }

    /**
     * Sets the current attempt count.
     *
     * @return $this
     */
    public function setAttemptCount(int $count) : RetryableJobContract
    {
        $this->setArgs([$count]);

        return $this;
    }

    /**
     * Handles a failed attempt by retrying the job if under the max attempts limit.
     */
    protected function handleFailedAttempt(Exception $exception) : void
    {
        $attemptCount = $this->getAttemptCount();

        if ($attemptCount < $this->getMaxAttempts()) {
            $this->retryJob($attemptCount + 1);
        }

        $this->onFailure($exception, $attemptCount);
    }

    /**
     * Retries the job by immediately re-queuing it with the given attempt count.
     */
    protected function retryJob(int $nextAttempt) : void
    {
        $this->setAttemptCount($nextAttempt);
        $this->reQueueJob();
    }

    /**
     * Hook called on every failure with the exception and the attempt count.
     *
     * @codeCoverageIgnore
     */
    protected function onFailure(Exception $exception, int $attemptCount) : void
    {
        // no-op
    }

    /**
     * Executes the job's business logic.
     *
     * @throws Exception
     */
    abstract protected function executeJob() : void;
}
