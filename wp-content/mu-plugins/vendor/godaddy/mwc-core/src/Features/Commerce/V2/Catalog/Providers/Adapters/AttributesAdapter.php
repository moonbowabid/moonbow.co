<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute as CommonAttribute;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\HasProductAttributeMappingServiceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AttributeValue;

/**
 * Adapter to convert Common {@see CommonAttribute} to V2 {@see Attribute} DTOs.
 */
class AttributesAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;
    use HasProductAttributeMappingServiceTrait;

    /** @var CommonAttribute[] */
    protected array $source;

    /**
     * ProductAttributesToAttributesAdapter constructor.
     *
     * @param CommonAttribute[] $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Convert `{@see CommonAttribute} objects to V2 {@see Attribute} DTOs.
     *
     * @return Attribute[]
     */
    public function convertFromSource() : array
    {
        if (empty($this->source)) {
            return [];
        }

        $validAttributes = [];
        foreach ($this->source as $attribute) {
            $validAttributes[] = $this->convertCommonAttributeToV2Attribute($attribute);
        }

        return $validAttributes;
    }

    /**
     * @return CommonAttribute[]
     */
    public function convertToSource() : array
    {
        // @todo add adapter to convert `mwc-common` TO V2 attributes
        return [];
    }

    /**
     * Convert {@see CommonAttribute} to V2 {@see Attribute} DTO.
     *
     * @param CommonAttribute $commonAttribute
     * @return Attribute
     */
    protected function convertCommonAttributeToV2Attribute(CommonAttribute $commonAttribute) : Attribute
    {
        $values = [];
        foreach ($commonAttribute->getValues() as $commonValue) {
            $attributeValueName = $this->getProductAttributeMappingService()->getRemoteAttributeValueNameForLocalAttributeValueSlug($commonAttribute->getName(), $commonValue->getName());

            $values[] = new AttributeValue([
                'name'     => $attributeValueName,
                'label'    => $commonValue->getLabel() ?: $attributeValueName ?: '',
                'position' => 0,
            ]);
        }

        return new Attribute([
            'name'     => $this->getProductAttributeMappingService()->getRemoteAttributeNameForLocalAttributeSlug($commonAttribute->getName()),
            'label'    => $commonAttribute->getLabel() ?: $commonAttribute->getName() ?: '',
            'position' => 0,
            'values'   => $values,
        ]);
    }
}
