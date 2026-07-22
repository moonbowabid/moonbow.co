<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class UpdateListOperation extends AbstractGraphQLOperation
{
    protected $operation = '
        mutation UpdateList($id: String!, $input: MutationUpdateListInput!) {
            updateList(id: $id, input: $input) {
                id
                name
                label
                description
                status
            }
        }';
}
