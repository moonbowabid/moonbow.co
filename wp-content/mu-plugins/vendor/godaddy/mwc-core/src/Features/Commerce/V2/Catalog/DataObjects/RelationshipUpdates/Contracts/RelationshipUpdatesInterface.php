<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\Contracts;

interface RelationshipUpdatesInterface
{
    /**
     * Build GraphQL variables for relationship updates.
     *
     * @return array<string, mixed>
     */
    public function buildGraphQLVariables() : array;

    /**
     * Build GraphQL mutation fragments for relationship updates.
     *
     * @param string $entityType The entity type (e.g., 'Sku', 'SkuGroup')
     * @param string $entityIdVar The GraphQL variable name for the entity ID (e.g., '$skuId', '$skuGroupId')
     * @return string[]
     */
    public function buildMutationFragments(string $entityType, string $entityIdVar) : array;

    /**
     * Build GraphQL variable definitions for relationship updates.
     *
     * @return string[]
     */
    public function buildVariableDefinitions() : array;

    /**
     * Determine if the entity ID variable is needed for the relationship updates.
     *
     * @return bool
     */
    public function needsEntityIdVariable() : bool;
}
