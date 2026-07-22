<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits\SkuGroupResponseFieldsTrait;

/**
 * GraphQL mutation operation for archiving a SKU Group in the v2 API.
 */
class ArchiveSkuGroupOperation extends AbstractGraphQLOperation
{
    use SkuGroupResponseFieldsTrait;

    public function __construct()
    {
        $this->operation = '
            mutation ArchiveSkuGroup($id: String!, $cascadeArchive: CascadeArchiveInput) {
                archiveSkuGroup(id: $id, cascadeArchive: $cascadeArchive) {'.
                    $this->getSkuGroupResponseFields().'
                }
            }';
    }
}
