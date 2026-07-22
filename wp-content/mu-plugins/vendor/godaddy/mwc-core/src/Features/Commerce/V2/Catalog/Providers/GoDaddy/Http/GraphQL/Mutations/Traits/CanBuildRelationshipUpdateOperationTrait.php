<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\AbstractRelationshipUpdate;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;

/**
 * Trait for building dynamic relationship update GraphQL operations.
 *
 * Provides shared logic for constructing GraphQL mutations based on relationship updates,
 * eliminating duplication between SKU and SkuGroup update operations.
 */
trait CanBuildRelationshipUpdateOperationTrait
{
    /** @var RelationshipUpdates */
    protected RelationshipUpdates $updates;

    /**
     * Build a dynamic GraphQL operation for relationship updates.
     *
     * @param string $entityType The entity type (e.g., 'Sku', 'SkuGroup')
     * @param string $entityIdVar The GraphQL variable for the entity ID (e.g., '$skuId', '$skuGroupId')
     * @param string $mutationName The name of the mutation (e.g., 'UpdateSkuRelationships')
     * @return string The complete GraphQL operation string
     */
    protected function buildDynamicRelationshipOperation(
        string $entityType,
        string $entityIdVar,
        string $mutationName
    ) : string {
        $mutations = [];
        $variableDefinitions = [];

        /** @var AbstractRelationshipUpdate[] $relationshipsToUpdate */
        $relationshipsToUpdate = array_filter([
            $this->updates->mediaUpdates ?? null,
            $this->updates->priceUpdates ?? null,
            $this->updates->channelUpdates ?? null,
            $this->updates->attributeUpdates ?? null,
            $this->updates->attributeValueUpdates ?? null,
            $this->updates->listUpdates ?? null,
        ]);

        $mutations = $this->getMutations($relationshipsToUpdate, $mutations, $entityType, $entityIdVar);
        $variableDefinitions = $this->getVariableDefinitions($relationshipsToUpdate, $variableDefinitions);

        // Only add entity ID variable if there are mutations that actually need it
        if ($this->needsEntityIdVariable($relationshipsToUpdate)) {
            array_unshift($variableDefinitions, $entityIdVar.': String!');
        }

        // @todo Add other relationship types (inventory, etc.)

        // If no mutations are needed, return a simple query that does nothing
        if (empty($mutations)) {
            return "mutation {$mutationName}({$entityIdVar}: String!) {
                __typename
            }";
        }

        $variableDefsString = implode(', ', $variableDefinitions);
        $mutationsString = implode("\n                ", $mutations);

        return "
            mutation {$mutationName}({$variableDefsString}) {
                {$mutationsString}
            }";
    }

    /**
     * @param AbstractRelationshipUpdate[] $relationshipsToUpdate
     * @param array<mixed> $mutations
     * @param string $entityType
     * @param string $entityIdVar
     * @return array<mixed>
     */
    protected function getMutations(array $relationshipsToUpdate, array $mutations, string $entityType, string $entityIdVar) : array
    {
        foreach ($relationshipsToUpdate as $update) {
            if ($update->hasUpdates()) {
                $mutations = array_merge($mutations, $update->buildMutationFragments($entityType, $entityIdVar));
            }
        }

        return $mutations;
    }

    /**
     * @param AbstractRelationshipUpdate[] $relationshipsToUpdate
     * @param array<mixed> $variableDefinitions
     * @return array<mixed>
     */
    protected function getVariableDefinitions(array $relationshipsToUpdate, array $variableDefinitions) : array
    {
        foreach ($relationshipsToUpdate as $update) {
            if ($update->hasUpdates()) {
                $variableDefinitions = array_merge($variableDefinitions, $update->buildVariableDefinitions());
            }
        }

        return $variableDefinitions;
    }

    /**
     * Determines if the entity ID variable is needed based on the relationship updates.
     *
     * @param AbstractRelationshipUpdate[] $relationshipsToUpdate
     * @return bool
     */
    protected function needsEntityIdVariable(array $relationshipsToUpdate) : bool
    {
        foreach ($relationshipsToUpdate as $update) {
            if ($update->hasUpdates() && $update->needsEntityIdVariable()) {
                return true;
            }
        }

        return false;
    }
}
