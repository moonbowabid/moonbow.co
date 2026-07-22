<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for creating a category/list in the v2 API.
 */
class CreateListOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation CreateList($input: MutationCreateListInput!) {
            createList(input: $input) {
                id
                name
                label
                description
                status
            }
        }';
}
