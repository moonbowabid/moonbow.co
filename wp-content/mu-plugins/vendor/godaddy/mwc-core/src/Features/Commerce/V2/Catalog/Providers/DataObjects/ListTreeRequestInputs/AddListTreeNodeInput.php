<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for adding a node to a list tree or parent node.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class AddListTreeNodeInput extends StoreIdRequestInput
{
    public string $listId;

    public ?string $listTreeId = null;

    public ?string $parentNodeId = null;

    public ?int $position = null;

    /**
     * Constructor.
     *
     * @param array{
     *     listId: string,
     *     listTreeId?: ?string,
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
