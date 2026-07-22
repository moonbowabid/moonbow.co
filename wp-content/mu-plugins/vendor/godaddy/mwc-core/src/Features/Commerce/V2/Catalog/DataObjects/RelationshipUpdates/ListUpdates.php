<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

class ListUpdates extends AbstractRelationshipUpdate
{
    /** @var string[] array of remote list IDs to be removed from the SKU Group */
    public array $toRemove = [];

    /** @var string[] array of remote list IDs to be added to the SKU Group */
    public array $toAdd = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Check if there are lists to remove.
     *
     * @return bool
     */
    public function hasListsToRemove() : bool
    {
        return ! empty($this->toRemove);
    }

    /**
     * Check if there are lists to add.
     *
     * @return bool
     */
    public function hasListsToAdd() : bool
    {
        return ! empty($this->toAdd);
    }

    /**
     * Build GraphQL variables for list updates.
     *
     * @return array<string, mixed>
     */
    public function buildGraphQLVariables() : array
    {
        $variables = [];

        if ($this->hasListsToAdd()) {
            $variables['listsToAdd'] = $this->toAdd;
        }

        if ($this->hasListsToRemove()) {
            $variables['listsToRemove'] = $this->toRemove;
        }

        return $variables;
    }

    /**
     * {@inheritDoc}
     */
    public function buildVariableDefinitions() : array
    {
        $definitions = [];

        if ($this->hasListsToRemove()) {
            $definitions[] = '$listsToRemove: [String!]!';
        }

        if ($this->hasListsToAdd()) {
            $definitions[] = '$listsToAdd: [String!]!';
        }

        return $definitions;
    }

    /**
     * {@inheritDoc}
     */
    public function buildMutationFragments(string $entityType, string $entityIdVar) : array
    {
        $mutations = [];

        // Add REMOVE mutations FIRST (order matters to avoid conflicts)
        if ($this->hasListsToRemove()) {
            $mutations[] = "removeLists: removeSkuGroupFromLists(id: {$entityIdVar}, input: { listIds: \$listsToRemove }) {
                id
            }";
        }

        // Add ADD mutations SECOND
        if ($this->hasListsToAdd()) {
            $mutations[] = "addLists: addSkuGroupToLists(id: {$entityIdVar}, input: { listIds: \$listsToAdd }) {
                id
            }";
        }

        return $mutations;
    }

    /** {@inheritDoc} */
    public function needsEntityIdVariable() : bool
    {
        return true;
    }
}
