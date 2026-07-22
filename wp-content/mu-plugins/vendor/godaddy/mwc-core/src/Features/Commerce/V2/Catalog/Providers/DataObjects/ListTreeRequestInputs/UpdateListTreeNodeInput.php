<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for updating a list tree node (move/re-parent).
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class UpdateListTreeNodeInput extends StoreIdRequestInput
{
    public string $listTreeNodeId;

    public ?string $parentNodeId = null;

    public ?int $position = null;

    /**
     * Constructor.
     *
     * @param array{
     *     listTreeNodeId: string,
     *     parentNodeId?: ?string,
     *     position?: ?int,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
