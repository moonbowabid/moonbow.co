<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for archiving a list tree.
 */
class ArchiveListTreeOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation ArchiveListTree($id: String!) {
            archiveListTree(id: $id) {
                id
            }
        }';
}
