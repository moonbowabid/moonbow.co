<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTree;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;

/**
 * Builds a ListTree data object from a GraphQL response that includes listTreeNodes edges.
 */
trait CanCreateListTreeFromResponseTrait
{
    /**
     * Creates a ListTree from GraphQL response data.
     *
     * @param array<string, mixed> $treeData
     * @return ListTree
     */
    protected function createListTreeFromResponse(array $treeData) : ListTree
    {
        $nodes = [];

        foreach (GraphQLHelper::extractGraphQLEdges($treeData, 'listTreeNodes') as $nodeData) {
            $listId = TypeHelper::string(ArrayHelper::get($nodeData, 'list.id'), '');

            $nodes[] = new ListTreeNode([
                'id'     => TypeHelper::string(ArrayHelper::get($nodeData, 'id'), ''),
                'listId' => $listId ?: null,
            ]);
        }

        return new ListTree([
            'id'            => TypeHelper::string(ArrayHelper::get($treeData, 'id'), ''),
            'listTreeNodes' => $nodes ?: null,
        ]);
    }
}
