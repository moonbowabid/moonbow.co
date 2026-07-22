<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for archiving a list tree.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class ArchiveListTreeInput extends StoreIdRequestInput
{
    public string $listTreeId;

    /**
     * Constructor.
     *
     * @param array{
     *     listTreeId: string,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
