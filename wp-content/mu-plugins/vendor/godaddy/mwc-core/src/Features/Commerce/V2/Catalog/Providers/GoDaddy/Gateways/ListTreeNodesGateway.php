<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Adapters\QueryListTreeNodesByListIdsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\DataObjects\QueryListTreeNodesByListIdsOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\ListTreeNodesGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTree;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\AddListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\ArchiveListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\CreateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\QueryListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\RemoveListTreeNodesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\UpdateListTreeNodeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\AddListTreeNodesToListTreeRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\AddListTreeNodesToParentNodeRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\ArchiveListTreeRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\CreateListTreeRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\QueryListTreeNodesRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\RemoveListTreeNodesRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\UpdateListTreeNodeRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees\UpdateListTreeRequestAdapter;

/**
 * Gateway for handling list tree node operations with the V2 API.
 */
class ListTreeNodesGateway extends AbstractGateway implements ListTreeNodesGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * Creates a list tree.
     *
     * @param CreateListTreeInput $input
     * @return ListTree
     * @throws CommerceExceptionContract
     */
    public function createListTree(CreateListTreeInput $input) : ListTree
    {
        /** @var ListTree $result */
        $result = $this->doAdaptedRequest(CreateListTreeRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Adds a node to a list tree.
     *
     * @param AddListTreeNodeInput $input
     * @return ListTree
     * @throws CommerceExceptionContract
     */
    public function addListTreeNodeToListTree(AddListTreeNodeInput $input) : ListTree
    {
        /** @var ListTree $result */
        $result = $this->doAdaptedRequest(AddListTreeNodesToListTreeRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Adds a node to a parent node.
     *
     * @param AddListTreeNodeInput $input
     * @return ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function addListTreeNodeToParent(AddListTreeNodeInput $input) : ListTreeNode
    {
        /** @var ListTreeNode $result */
        $result = $this->doAdaptedRequest(AddListTreeNodesToParentNodeRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a list tree.
     *
     * @param UpdateListTreeInput $input
     * @return ListTree
     * @throws CommerceExceptionContract
     */
    public function updateListTree(UpdateListTreeInput $input) : ListTree
    {
        /** @var ListTree $result */
        $result = $this->doAdaptedRequest(UpdateListTreeRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a list tree node.
     *
     * @param UpdateListTreeNodeInput $input
     * @return ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function updateListTreeNode(UpdateListTreeNodeInput $input) : ListTreeNode
    {
        /** @var ListTreeNode $result */
        $result = $this->doAdaptedRequest(UpdateListTreeNodeRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Removes nodes from a list tree.
     *
     * @param RemoveListTreeNodesInput $input
     * @throws CommerceExceptionContract
     */
    public function removeListTreeNodesFromListTree(RemoveListTreeNodesInput $input) : void
    {
        $this->doAdaptedRequest(RemoveListTreeNodesRequestAdapter::getNewInstance($input));
    }

    /**
     * Archives a list tree.
     *
     * @param ArchiveListTreeInput $input
     * @throws CommerceExceptionContract
     */
    public function archiveListTree(ArchiveListTreeInput $input) : void
    {
        $this->doAdaptedRequest(ArchiveListTreeRequestAdapter::getNewInstance($input));
    }

    /**
     * Queries for a ListTreeNode by its associated list ID.
     *
     * @param QueryListTreeNodesInput $input
     * @return ?ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function queryByListId(QueryListTreeNodesInput $input) : ?ListTreeNode
    {
        /** @var ?ListTreeNode $result */
        $result = $this->doAdaptedRequest(QueryListTreeNodesRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Queries list tree nodes by their associated list IDs.
     *
     * @throws CommerceExceptionContract
     */
    public function queryByListIds(QueryListTreeNodesByListIdsInput $input) : QueryListTreeNodesByListIdsOutput
    {
        /** @var QueryListTreeNodesByListIdsOutput $result */
        $result = $this->doAdaptedRequest(QueryListTreeNodesByListIdsRequestAdapter::getNewInstance($input));

        return $result;
    }
}
