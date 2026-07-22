<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Input data object for v2 API requests that require a store ID.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class StoreIdRequestInput extends AbstractDataObject
{
    public string $storeId;

    /**
     * Constructor.
     *
     * @param array{
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
