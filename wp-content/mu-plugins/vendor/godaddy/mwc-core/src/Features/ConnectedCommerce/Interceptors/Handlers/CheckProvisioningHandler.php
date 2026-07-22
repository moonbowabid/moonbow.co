<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\Handlers;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Common\Stores\Contracts\StoreRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Exceptions\GoDaddyPaymentsConfigurationUpdateFailed;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Interceptors\CheckProvisioningInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\Contracts\ProvisioningServiceContract;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;

/**
 * Checks provisioning status and transitions state when provisioning completes or fails.
 */
class CheckProvisioningHandler extends AbstractInterceptorHandler
{
    protected ProvisioningServiceContract $provisioningService;

    protected StoreRepositoryContract $storeRepository;

    public function __construct(ProvisioningServiceContract $provisioningService, StoreRepositoryContract $storeRepository)
    {
        $this->provisioningService = $provisioningService;
        $this->storeRepository = $storeRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        try {
            $contextId = $this->provisioningService->getProvisioningContextId();

            if (! $contextId) {
                $this->provisioningService->setProvisioningStatus('FAILED');

                return;
            }

            $provisioningContext = $this->provisioningService->getProvisioningContext(
                new GetProvisioningContextInput(['contextId' => $contextId])
            );

            $this->handleProvisioningStatus($provisioningContext);
        } catch (CommerceExceptionContract|Exception $exception) {
            SentryException::getNewInstance('Failed to check Connected Commerce provisioning status.', $exception);
        }
    }

    /**
     * Handles the provisioning status transition.
     */
    protected function handleProvisioningStatus(ProvisioningContext $context) : void
    {
        switch ($context->provisioningStatus) {
            case 'PENDING':
                $this->scheduleNextCheck();
                break;
            case 'IN_PROGRESS':
                $this->handleInProgress($context->provisioningStatus);
                break;
            case 'COMPLETE':
                $this->handleComplete($context->provisioningStatus, $context);
                break;
            case 'FAILED':
                $this->handleFailed($context->provisioningStatus);
                break;
        }
    }

    /**
     * Handles in-progress provisioning.
     */
    protected function handleInProgress(string $status) : void
    {
        $this->provisioningService->setProvisioningStatus($status);

        $this->scheduleNextCheck();
    }

    /**
     * Handles successful provisioning completion.
     */
    protected function handleComplete(string $status, ProvisioningContext $context) : void
    {
        if ($context->storeId) {
            $this->storeRepository->setDefaultStoreId($context->storeId);
        }

        $this->updateGoDaddyPaymentsStore($context);

        $this->provisioningService->setProvisioningStatus($status);
    }

    /**
     * Reconciles the GoDaddy Payments store configuration with the provisioning context.
     *
     * Only updates when the merchant-selected store belongs to the same business as the currently
     * connected GoDaddy Payments account. The cross-business case is logged to Sentry and skipped
     * so existing credentials are not left pointing at an unauthorized business.
     */
    protected function updateGoDaddyPaymentsStore(ProvisioningContext $context) : void
    {
        try {
            if (! $context->businessId || ! $context->storeId) {
                return;
            }

            $currentBusinessId = Poynt::getBusinessId();

            if (! $currentBusinessId) {
                GoDaddyPaymentsConfigurationUpdateFailed::getNewInstance(sprintf(
                    'GoDaddy Payments is not connected; skipping GoDaddy Payments config update. Provisioning business ID: %s.',
                    $context->businessId
                ));

                return;
            }

            if ($currentBusinessId !== $context->businessId) {
                GoDaddyPaymentsConfigurationUpdateFailed::getNewInstance(sprintf(
                    'Connected Commerce Store and GoDaddy Payments Store belong to different businesses; skipping GoDaddy Payments config update. Provisioning business ID: %s, GoDaddy Payments business ID: %s.',
                    $context->businessId,
                    $currentBusinessId
                ));

                return;
            }

            Poynt::setBusinessId($context->businessId);
            Poynt::setSiteStoreId($context->storeId);
        } catch (Exception $exception) {
            GoDaddyPaymentsConfigurationUpdateFailed::getNewInstance('Failed to update GoDaddy Payments store after provisioning.', $exception);
        }
    }

    /**
     * Handles provisioning failure.
     */
    protected function handleFailed(string $status) : void
    {
        $this->provisioningService->setProvisioningStatus($status);
    }

    /**
     * Schedules the next provisioning check as a single action.
     */
    protected function scheduleNextCheck() : void
    {
        try {
            Schedule::singleAction()
                ->setName(CheckProvisioningInterceptor::JOB_NAME)
                ->setUniqueByName()
                ->setScheduleAt((new DateTime('now'))->add(new DateInterval('PT1M')))
                ->schedule();
        } catch (Exception $exception) {
            SentryException::getNewInstance('Failed to schedule next Connected Commerce provisioning check.', $exception);
        }
    }
}
