<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for creating a list tree in the v2 API.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class CreateListTreeInput extends StoreIdRequestInput
{
    public string $label;

    public ?string $name = null;

    public ?string $description = null;

    public ?string $status = null;

    /** @var array<mixed> */
    public array $listTreeNodes = [];

    /**
     * Constructor.
     *
     * @param array{
     *     label: string,
     *     name?: ?string,
     *     description?: ?string,
     *     status?: ?string,
     *     listTreeNodes?: array<mixed>,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
