<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;

/**
 * Provides the ability to build a ProvisioningContext from API response data.
 */
trait CanBuildProvisioningContextTrait
{
    /**
     * Builds a ProvisioningContext from API response data.
     *
     * @param array<string, mixed> $data
     */
    protected function buildProvisioningContext(array $data) : ProvisioningContext
    {
        $provisioning = ArrayHelper::getArrayValueForKey($data, 'provisioning');

        return new ProvisioningContext([
            'contextId'           => TypeHelper::string(ArrayHelper::get($data, 'contextId'), ''),
            'customerId'          => TypeHelper::stringOrNull(ArrayHelper::get($data, 'customerId')),
            'businessId'          => TypeHelper::stringOrNull(ArrayHelper::get($data, 'businessId')),
            'storeId'             => TypeHelper::stringOrNull(ArrayHelper::get($data, 'storeId')),
            'channelId'           => TypeHelper::stringOrNull(ArrayHelper::get($data, 'channelId')),
            'provisioningStatus'  => TypeHelper::string(ArrayHelper::get($provisioning, 'status'), 'PENDING'),
            'provisioningMessage' => TypeHelper::stringOrNull(ArrayHelper::get($provisioning, 'message')),
        ]);
    }
}
