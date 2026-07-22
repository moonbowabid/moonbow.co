<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for updating list tree metadata.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class UpdateListTreeInput extends StoreIdRequestInput
{
    public string $listTreeId;

    public ?string $label = null;

    public ?string $description = null;

    /**
     * Constructor.
     *
     * @param array{
     *     listTreeId: string,
     *     label?: ?string,
     *     description?: ?string,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
