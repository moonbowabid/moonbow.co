<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTree;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\AddListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\ArchiveListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\CreateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\QueryListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\RemoveListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeNodeInput;

/**
 * Contract for list tree node gateways.
 */
interface ListTreeNodesGatewayContract
{
    /**
     * Creates a list tree.
     *
     * @param CreateListTreeInput $input
     * @return ListTree
     * @throws CommerceExceptionContract
     */
    public function createListTree(CreateListTreeInput $input) : ListTree;

    /**
     * Adds a node to a list tree.
     *
     * @param AddListTreeNodeInput $input
     * @return ListTree
     * @throws CommerceExceptionContract
     */
    public function addListTreeNodeToListTree(AddListTreeNodeInput $input) : ListTree;

    /**
     * Adds a node to a parent node.
     *
     * @param AddListTreeNodeInput $input
     * @return ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function addListTreeNodeToParent(AddListTreeNodeInput $input) : ListTreeNode;

    /**
     * Updates a list tree.
     *
     * @param UpdateListTreeInput $input
     * @return ListTree
     * @throws CommerceExceptionContract
     */
    public function updateListTree(UpdateListTreeInput $input) : ListTree;

    /**
     * Updates a list tree node.
     *
     * @param UpdateListTreeNodeInput $input
     * @return ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function updateListTreeNode(UpdateListTreeNodeInput $input) : ListTreeNode;

    /**
     * Removes nodes from a list tree.
     *
     * @param RemoveListTreeNodesInput $input
     * @throws CommerceExceptionContract
     */
    public function removeListTreeNodesFromListTree(RemoveListTreeNodesInput $input) : void;

    /**
     * Archives a list tree.
     *
     * @param ArchiveListTreeInput $input
     * @throws CommerceExceptionContract
     */
    public function archiveListTree(ArchiveListTreeInput $input) : void;

    /**
     * Queries for a ListTreeNode by its associated list ID.
     *
     * @param QueryListTreeNodesInput $input
     * @return ?ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function queryByListId(QueryListTreeNodesInput $input) : ?ListTreeNode;

    /**
     * Queries list tree nodes by their associated list IDs.
     *
     * @throws CommerceExceptionContract
     */
    public function queryByListIds(QueryListTreeNodesByListIdsInput $input) : QueryListTreeNodesByListIdsOutput;
}
