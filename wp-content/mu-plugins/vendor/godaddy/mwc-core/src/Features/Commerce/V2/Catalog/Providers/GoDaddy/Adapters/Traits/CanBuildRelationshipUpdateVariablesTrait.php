<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\AbstractRelationshipUpdate;

trait CanBuildRelationshipUpdateVariablesTrait
{
    /**
     * Builds an array of GraphQL variables for the provided relationship updates.
     *
     * @param AbstractRelationshipUpdate[] $relationshipsToUpdate
     * @param array<string,mixed> $variables
     * @return array<string,mixed>
     */
    protected function getVariables(array $relationshipsToUpdate, array $variables) : array
    {
        foreach ($relationshipsToUpdate as $update) {
            if ($update->hasUpdates()) {
                $updateVariables = $update->buildGraphQLVariables();
                $variables = array_merge($variables, $updateVariables);
            }
        }

        return $variables;
    }
}
