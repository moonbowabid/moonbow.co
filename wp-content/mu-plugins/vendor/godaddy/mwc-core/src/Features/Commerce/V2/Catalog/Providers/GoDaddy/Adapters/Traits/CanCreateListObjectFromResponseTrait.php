<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;

/**
 * Trait for creating ListObject instances from GraphQL response data.
 */
trait CanCreateListObjectFromResponseTrait
{
    use CanGetOptionalValuesFromResponseTrait;

    /**
     * Creates a ListObject instance from GraphQL response data.
     *
     * @param array<string, mixed> $listData
     * @return ListObject
     */
    protected function createListObjectFromResponse(array $listData) : ListObject
    {
        return new ListObject([
            'id'              => TypeHelper::string(ArrayHelper::get($listData, 'id'), ''),
            'name'            => TypeHelper::string(ArrayHelper::get($listData, 'name'), ''),
            'label'           => TypeHelper::string(ArrayHelper::get($listData, 'label'), ''),
            'description'     => $this->getOptionalStringFromResponse($listData, 'description'),
            'htmlDescription' => $this->getOptionalStringFromResponse($listData, 'htmlDescription'),
            'status'          => TypeHelper::string(ArrayHelper::get($listData, 'status'), 'DRAFT'),
            'createdAt'       => $this->getOptionalStringFromResponse($listData, 'createdAt'),
            'updatedAt'       => $this->getOptionalStringFromResponse($listData, 'updatedAt'),
        ]);
    }
}
