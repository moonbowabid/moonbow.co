<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Attribute;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits\CanCleanAttributeDataTrait;

class AttributeUpdates extends AbstractRelationshipUpdate
{
    use CanCleanAttributeDataTrait;
    /** @var Attribute[] */
    public array $toAdd = [];

    /** @var string[] */
    public array $toRemove = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Check if there are attributes to remove.
     *
     * @return bool
     */
    public function hasAttributesToRemove() : bool
    {
        return ! empty($this->toRemove);
    }

    /**
     * Check if there are attributes to add.
     *
     * @return bool
     */
    public function hasAttributesToAdd() : bool
    {
        return ! empty($this->toAdd);
    }

    /**
     * Build GraphQL variables for attribute updates.
     *
     * @return array<string, mixed>
     */
    public function buildGraphQLVariables() : array
    {
        $variables = [];

        if ($this->hasAttributesToAdd()) {
            $variables['attributesToAdd'] = $this->buildAttributesInput($this->toAdd);
        }

        if ($this->hasAttributesToRemove()) {
            $variables['attributesToRemove'] = $this->toRemove;
        }

        return $variables;
    }

    /**
     * Build attributes input for GraphQL mutations.
     *
     * @param Attribute[] $attributes Array of Attribute instances
     * @return array<int, array<string, mixed>> Array of attribute input data for GraphQL
     */
    protected function buildAttributesInput(array $attributes) : array
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $data = $attribute->toArray();
            $result[] = $this->cleanSingleAttributeForCreation($data);
        }

        return $result;
    }

    /**
     * Build mutation fragments for adding and removing attributes.
     *
     * @param string $entityType
     * @param string $entityIdVar
     * @return string[]
     */
    public function buildMutationFragments(string $entityType, string $entityIdVar) : array
    {
        $mutations = [];

        // Add REMOVE mutations FIRST (order matters to avoid conflicts)
        if ($this->hasAttributesToRemove()) {
            $mutations[] = "removeAttributes: removeAttributesFrom{$entityType}(id: {$entityIdVar}, input: { attributeIds: \$attributesToRemove }) {
                id
            }";
        }

        // Add ADD mutations SECOND
        if ($this->hasAttributesToAdd()) {
            $mutations[] = "addAttributes: addAttributesTo{$entityType}(id: {$entityIdVar}, input: { attributes: \$attributesToAdd }) {
                id
            }";
        }

        return $mutations;
    }

    /**
     * Build GraphQL variable definitions for attribute updates.
     *
     * @return string[]
     */
    public function buildVariableDefinitions() : array
    {
        $definitions = [];

        if ($this->hasAttributesToRemove()) {
            $definitions[] = '$attributesToRemove: [String!]!';
        }

        if ($this->hasAttributesToAdd()) {
            $definitions[] = '$attributesToAdd: [CreateSKUGroupAttributeInput!]!';
        }

        return $definitions;
    }

    /**
     * {@inheritDoc}
     */
    public function needsEntityIdVariable() : bool
    {
        return true;
    }
}
