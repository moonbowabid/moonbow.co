<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Represents a provisioning context returned from the MWC API.
 */
class ProvisioningContext extends AbstractDataObject
{
    public string $contextId;

    public ?string $customerId = null;

    public ?string $businessId = null;

    public ?string $storeId = null;

    public ?string $channelId = null;

    public string $provisioningStatus;

    public ?string $provisioningMessage = null;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     contextId: string,
     *     customerId?: ?string,
     *     businessId?: ?string,
     *     storeId?: ?string,
     *     channelId?: ?string,
     *     provisioningStatus: string,
     *     provisioningMessage?: ?string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
