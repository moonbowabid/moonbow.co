<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Input data for the connectExistingSite mutation.
 */
class ConnectExistingSiteInput extends AbstractDataObject
{
    public string $businessId;

    public string $storeId;

    public string $siteUid;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     businessId: string,
     *     storeId: string,
     *     siteUid: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
