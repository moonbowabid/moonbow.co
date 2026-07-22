<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Skus\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\GraphQLHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Metafield;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuPrice;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertResponseToAttributeTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCreateSkuGroupFromResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanGetOptionalValuesFromResponseTrait;

/**
 * Trait for creating Sku objects from GraphQL response data.
 */
trait CanConvertSkuResponseToOutputTrait
{
    use CanConvertResponseToAttributeTrait;
    use CanCreateSkuGroupFromResponseTrait;
    use CanGetOptionalValuesFromResponseTrait;

    /**
     * Converts GraphQL SKU response data to SkuRequestOutput.
     *
     * @param mixed $skuData Raw SKU response data from GraphQL
     * @return SkuRequestOutput
     * @throws MissingProductRemoteIdException
     */
    protected function convertSkuResponseToOutput($skuData) : SkuRequestOutput
    {
        $skuId = TypeHelper::string(ArrayHelper::get($skuData, 'id'), '');

        if (empty($skuId)) {
            throw new MissingProductRemoteIdException('The SKU ID was not returned from the response.');
        }

        // Extract SKU Group data from response
        /** @var array<string, mixed> $skuGroupData */
        $skuGroupData = TypeHelper::array(ArrayHelper::get($skuData, 'skuGroup'), []);
        $skuGroup = $this->createSkuGroupFromResponse($skuGroupData);

        // Create new Sku object from response data
        /** @var array<string, mixed> $skuData */
        $skuData = TypeHelper::array($skuData, []);
        $sku = $this->createSkuFromResponse($skuData);

        return new SkuRequestOutput([
            'sku'      => $sku,
            'skuGroup' => $skuGroup,
        ]);
    }

    /**
     * Creates a Sku object from GraphQL response data.
     *
     * @param array<string, mixed> $skuData
     * @return Sku
     */
    protected function createSkuFromResponse(array $skuData) : Sku
    {
        return new Sku([
            'id'                       => TypeHelper::string(ArrayHelper::get($skuData, 'id'), ''),
            'name'                     => TypeHelper::string(ArrayHelper::get($skuData, 'name'), ''),
            'label'                    => TypeHelper::string(ArrayHelper::get($skuData, 'label'), ''),
            'code'                     => TypeHelper::string(ArrayHelper::get($skuData, 'code'), ''),
            'description'              => $this->getOptionalStringFromResponse($skuData, 'description'),
            'htmlDescription'          => $this->getOptionalStringFromResponse($skuData, 'htmlDescription'),
            'status'                   => TypeHelper::string(ArrayHelper::get($skuData, 'status'), 'DRAFT'),
            'createdAt'                => $this->getOptionalStringFromResponse($skuData, 'createdAt'),
            'updatedAt'                => $this->getOptionalStringFromResponse($skuData, 'updatedAt'),
            'archivedAt'               => $this->getOptionalStringFromResponse($skuData, 'archivedAt'),
            'eanCode'                  => $this->getOptionalStringFromResponse($skuData, 'eanCode'),
            'gtinCode'                 => $this->getOptionalStringFromResponse($skuData, 'gtinCode'),
            'upcCode'                  => $this->getOptionalStringFromResponse($skuData, 'upcCode'),
            'weight'                   => $this->getOptionalFloatFromResponse($skuData, 'weight'),
            'unitOfWeight'             => $this->getOptionalStringFromResponse($skuData, 'unitOfWeight'),
            'disableInventoryTracking' => TypeHelper::bool(ArrayHelper::get($skuData, 'disableInventoryTracking'), false),
            'disableShipping'          => TypeHelper::bool(ArrayHelper::get($skuData, 'disableShipping'), false),
            'backorderLimit'           => $this->getOptionalIntFromResponse($skuData, 'backorderLimit'),
            'skuGroupId'               => TypeHelper::string(ArrayHelper::get($skuData, 'skuGroup.id'), ''),
            'locations'                => [], // TODO: Convert when location support is added
            'attributeValues'          => $this->convertAttributeValues(
                GraphQLHelper::extractGraphQLEdges($skuData, 'attributeValues')
            ),
            'attributes' => $this->convertAttributes(
                GraphQLHelper::extractGraphQLEdges($skuData, 'attributes')
            ),
            'mediaObjects' => $this->createMediaObjectsFromResponse($skuData),
            'prices'       => $this->convertPricesFromResponse(GraphQLHelper::extractGraphQLEdges($skuData, 'prices')),
            'metafields'   => array_merge(
                $this->convertMetafields(GraphQLHelper::extractGraphQLEdges($skuData, 'commerceAppsMetafields')),
                $this->convertMetafields(GraphQLHelper::extractGraphQLEdges($skuData, 'catalogV1Metafields'))
            ),
        ]);
    }

    /**
     * Converts GraphQL prices node data to SkuPrice objects.
     *
     * @param array<mixed> $priceNodes Array of price node data (edges already extracted)
     * @return SkuPrice[]
     */
    protected function convertPricesFromResponse(array $priceNodes) : array
    {
        $prices = [];

        foreach ($priceNodes as $node) {
            if (empty($node)) {
                continue;
            }

            $priceId = TypeHelper::string(ArrayHelper::get($node, 'id'), '');
            $valueData = TypeHelper::array(ArrayHelper::get($node, 'value'), []);
            $compareAtValueData = TypeHelper::array(ArrayHelper::get($node, 'compareAtValue'), []);

            $value = null;
            if (! empty($valueData)) {
                $value = SimpleMoney::from(
                    TypeHelper::string(ArrayHelper::get($valueData, 'currencyCode'), 'USD'),
                    TypeHelper::int(ArrayHelper::get($valueData, 'value'), 0)
                );
            }

            $compareAtValue = null;
            if (! empty($compareAtValueData)) {
                $compareAtValue = SimpleMoney::from(
                    TypeHelper::string(ArrayHelper::get($compareAtValueData, 'currencyCode'), 'USD'),
                    TypeHelper::int(ArrayHelper::get($compareAtValueData, 'value'), 0)
                );
            }

            if ($value) {
                $prices[] = new SkuPrice([
                    'id'             => $priceId ?: null,
                    'value'          => $value,
                    'compareAtValue' => $compareAtValue,
                ]);
            }
        }

        return $prices;
    }

    /**
     * Converts GraphQL metafields node data to Metafield objects.
     *
     * @param array<mixed> $metafieldNodes Array of metafield node data (edges already extracted)
     * @return Metafield[]
     */
    protected function convertMetafields(array $metafieldNodes) : array
    {
        return array_map(function ($node) {
            $nodeData = TypeHelper::arrayOfStringsAsKeys($node);

            return new Metafield([
                'namespace' => TypeHelper::string(ArrayHelper::get($nodeData, 'namespace'), ''),
                'key'       => TypeHelper::string(ArrayHelper::get($nodeData, 'key'), ''),
                'value'     => $this->getOptionalStringFromResponse($nodeData, 'value'),
                'type'      => $this->getOptionalStringFromResponse($nodeData, 'type'),
            ]);
        }, $metafieldNodes);
    }
}
