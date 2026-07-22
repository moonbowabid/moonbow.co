<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for archiving a list in the v2 API.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class ArchiveListInput extends StoreIdRequestInput
{
    public string $listId;

    /**
     * Constructor.
     *
     * @param array{
     *     listId: string,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
