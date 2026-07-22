<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\AttributeValue;

class AttributeValueUpdates extends AbstractRelationshipUpdate
{
    /**
     * Map of attribute ID to array of AttributeValue objects to add.
     * Structure: ['attributeId1' => [AttributeValue, ...], 'attributeId2' => [...], ...].
     *
     * @var array<string, AttributeValue[]>
     */
    public array $toAdd = [];

    /**
     * Map of attribute ID to array of attribute value IDs to remove.
     * Structure: ['attributeId1' => ['valueId1', 'valueId2'], 'attributeId2' => [...], ...].
     *
     * @var array<string, string[]>
     */
    public array $toRemove = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Check if there are attribute values to add for any attribute.
     *
     * @return bool
     */
    public function hasAttributeValuesToAdd() : bool
    {
        return ! empty(array_filter($this->toAdd));
    }

    /**
     * Check if there are attribute values to remove from any attribute.
     *
     * @return bool
     */
    public function hasAttributeValuesToRemove() : bool
    {
        return ! empty(array_filter($this->toRemove));
    }

    /**
     * Get all attribute IDs that have value changes (additions or removals).
     *
     * @return string[]
     */
    public function getAffectedAttributeIds() : array
    {
        $addAttributeIds = array_keys(array_filter($this->toAdd));
        $removeAttributeIds = array_keys(array_filter($this->toRemove));

        return array_unique(array_merge($addAttributeIds, $removeAttributeIds));
    }

    /**
     * Check if a specific attribute has values to add.
     *
     * @param string $attributeId
     * @return bool
     */
    public function hasValuesToAddForAttribute(string $attributeId) : bool
    {
        return ! empty($this->toAdd[$attributeId] ?? []);
    }

    /**
     * Check if a specific attribute has values to remove.
     *
     * @param string $attributeId
     * @return bool
     */
    public function hasValuesToRemoveForAttribute(string $attributeId) : bool
    {
        return ! empty($this->toRemove[$attributeId] ?? []);
    }

    /**
     * Build GraphQL variables for attribute value additions and removals.
     * This creates variables for each affected attribute.
     *
     * @return array<string, mixed>
     */
    public function buildGraphQLVariables() : array
    {
        $variables = [];

        foreach ($this->getAffectedAttributeIds() as $attributeId) {
            // Create unique variable names for each attribute
            $varSuffix = $this->sanitizeAttributeIdForVariable($attributeId);

            if ($this->hasValuesToRemoveForAttribute($attributeId) && isset($this->toRemove[$attributeId])) {
                $variables["valuesToRemoveFrom{$varSuffix}"] = $this->toRemove[$attributeId];
            }

            if ($this->hasValuesToAddForAttribute($attributeId) && isset($this->toAdd[$attributeId])) {
                $variables["valuesToAddTo{$varSuffix}"] = $this->buildAttributeValueInput($this->toAdd[$attributeId], $attributeId);
            }
        }

        return $variables;
    }

    /**
     * Build attribute value input for GraphQL createAttributeValues mutation.
     *
     * @param AttributeValue[] $attributeValues Array of AttributeValue instances
     * @param string $attributeId The parent attribute ID
     * @return array<int, array<string, mixed>> Array of attribute value input data for GraphQL
     */
    protected function buildAttributeValueInput(array $attributeValues, string $attributeId) : array
    {
        $result = [];
        foreach ($attributeValues as $attributeValue) {
            // Remove 'id' field for creation input as GraphQL doesn't expect it
            $data = ArrayHelper::except($attributeValue->toArray(), 'id');
            // Add required attributeId for CreateAttributeValueInput
            $data['attributeId'] = $attributeId;
            $result[] = TypeHelper::arrayOfStringsAsKeys($data);
        }

        return $result;
    }

    /**
     * Sanitize attribute ID for use as GraphQL variable name.
     * Replace non-alphanumeric characters with underscores.
     *
     * @param string $attributeId
     * @return ?string
     */
    protected function sanitizeAttributeIdForVariable(string $attributeId) : ?string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', $attributeId);
    }

    /**
     * {@inheritDoc}
     */
    public function buildVariableDefinitions() : array
    {
        $definitions = [];

        foreach ($this->getAffectedAttributeIds() as $attributeId) {
            $varSuffix = $this->sanitizeAttributeIdForVariable($attributeId);

            if ($this->hasValuesToRemoveForAttribute($attributeId)) {
                $definitions[] = "\$valuesToRemoveFrom{$varSuffix}: [String!]!";
            }

            if ($this->hasValuesToAddForAttribute($attributeId)) {
                $definitions[] = "\$valuesToAddTo{$varSuffix}: [CreateAttributeValueInput!]!";
            }
        }

        return $definitions;
    }

    /**
     * {@inheritDoc}
     */
    public function buildMutationFragments(string $entityType, string $entityIdVar) : array
    {
        $mutations = [];

        // Process each affected attribute - both additions and removals are supported
        foreach ($this->getAffectedAttributeIds() as $attributeId) {
            $varSuffix = $this->sanitizeAttributeIdForVariable($attributeId);

            // Add REMOVE mutations FIRST (order matters to avoid conflicts)
            if ($this->hasValuesToRemoveForAttribute($attributeId)) {
                $mutations[] = "removeValuesFrom{$varSuffix}: removeAttributeValuesFromAttribute(id: \"{$attributeId}\", input: { attributeValueIds: \$valuesToRemoveFrom{$varSuffix} }) {
                    id
                }";
            }

            // Add ADD mutations SECOND
            if ($this->hasValuesToAddForAttribute($attributeId)) {
                $mutations[] = "addValuesTo{$varSuffix}: createAttributeValues(input: \$valuesToAddTo{$varSuffix}) {
                    id
                }";
            }
        }

        return $mutations;
    }

    /**
     * {@inheritDoc}
     */
    public function needsEntityIdVariable() : bool
    {
        // Attribute value mutations use individual attribute IDs, not the entity ID
        return false;
    }
}
