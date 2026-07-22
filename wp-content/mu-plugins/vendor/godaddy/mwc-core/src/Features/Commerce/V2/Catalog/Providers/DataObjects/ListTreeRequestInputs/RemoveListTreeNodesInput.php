<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\StoreIdRequestInput;

/**
 * Input data object for removing nodes from a list tree.
 *
 * @method static static getNewInstance(array<string, mixed> $data)
 */
class RemoveListTreeNodesInput extends StoreIdRequestInput
{
    public string $listTreeId;

    /** @var string[] */
    public array $listTreeNodeIds;

    /**
     * Constructor.
     *
     * @param array{
     *     listTreeId: string,
     *     listTreeNodeIds: string[],
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
