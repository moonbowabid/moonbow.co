<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AttributeValue;

/**
 * Trait for converting GraphQL attribute response data to Attribute objects.
 */
trait CanConvertResponseToAttributeTrait
{
    /**
     * Converts GraphQL attribute node data to Attribute objects.
     *
     * @param array<mixed> $attributesResponse
     * @return Attribute[]
     */
    protected function convertAttributes(array $attributesResponse) : array
    {
        $attributes = [];

        foreach ($attributesResponse as $attributeData) {
            if (empty($attributeData)) {
                continue;
            }

            $attributes[] = new Attribute([
                'id'       => TypeHelper::string(ArrayHelper::get($attributeData, 'id'), ''),
                'name'     => TypeHelper::string(ArrayHelper::get($attributeData, 'name'), ''),
                'label'    => TypeHelper::string(ArrayHelper::get($attributeData, 'label'), ''),
                'position' => TypeHelper::int(ArrayHelper::get($attributeData, 'position'), 0),
                'values'   => $this->convertAttributeValues(
                    GraphQLHelper::extractGraphQLEdges(TypeHelper::arrayOfStringsAsKeys($attributeData), 'values')
                ),
            ]);
        }

        return $attributes;
    }

    /**
     * Converts GraphQL attribute value node data to AttributeValue objects.
     *
     * @param array<mixed> $attributeValuesResponse
     * @return AttributeValue[]
     */
    protected function convertAttributeValues(array $attributeValuesResponse) : array
    {
        $attributeValues = [];

        foreach ($attributeValuesResponse as $attributeValueData) {
            if (empty($attributeValueData)) {
                continue;
            }

            $attributeValues[] = new AttributeValue([
                'id'       => TypeHelper::string(ArrayHelper::get($attributeValueData, 'id'), ''),
                'name'     => TypeHelper::string(ArrayHelper::get($attributeValueData, 'name'), ''),
                'label'    => TypeHelper::string(ArrayHelper::get($attributeValueData, 'label'), ''),
                'position' => TypeHelper::int(ArrayHelper::get($attributeValueData, 'position'), 0),
            ]);
        }

        return $attributeValues;
    }
}
