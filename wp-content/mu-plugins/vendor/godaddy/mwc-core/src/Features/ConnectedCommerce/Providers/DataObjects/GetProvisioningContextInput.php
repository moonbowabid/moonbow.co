<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Input data for querying a provisioning context by ID.
 */
class GetProvisioningContextInput extends AbstractDataObject
{
    public string $contextId;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     contextId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
