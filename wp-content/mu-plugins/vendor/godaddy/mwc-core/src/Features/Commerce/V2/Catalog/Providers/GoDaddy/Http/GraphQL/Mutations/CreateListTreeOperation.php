<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL mutation operation for creating a list tree.
 */
class CreateListTreeOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation CreateListTree($input: MutationCreateListTreeInput!) {
            createListTree(input: $input) {
                id
                listTreeNodes {
                    edges {
                        node {
                            id
                            list {
                                id
                            }
                        }
                    }
                }
            }
        }';
}
