<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation that archives a list in the v2 API.
 *
 * `archiveList` is the catalog-subgraph's soft-delete; there is no hard-delete
 * mutation for lists today.
 */
class ArchiveListOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation ArchiveList($id: String!) {
            archiveList(id: $id) {
                id
                name
                label
                description
                status
            }
        }';
}
