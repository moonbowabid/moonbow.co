<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

class ChannelUpdates extends AbstractRelationshipUpdate
{
    /** @var string[] array of channel IDs to be removed */
    public array $toRemove = [];

    /** @var string[] array of channel IDs to be added */
    public array $toAdd = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Check if there are channels to remove.
     */
    public function hasChannelsToRemove() : bool
    {
        return ! empty($this->toRemove);
    }

    /**
     * Check if there are channels to add.
     */
    public function hasChannelsToAdd() : bool
    {
        return ! empty($this->toAdd);
    }

    /**
     * Build GraphQL variables for channel updates.
     */
    public function buildGraphQLVariables() : array
    {
        $variables = [];

        if ($this->hasChannelsToRemove()) {
            $variables['channelIdsToRemove'] = $this->toRemove;
        }

        if ($this->hasChannelsToAdd()) {
            $variables['channelIdsToAdd'] = $this->toAdd;
        }

        return $variables;
    }

    /**
     * Build GraphQL variable definitions for channel updates.
     */
    public function buildVariableDefinitions() : array
    {
        $definitions = [];

        if ($this->hasChannelsToRemove()) {
            $definitions[] = '$channelIdsToRemove: [String!]!';
        }

        if ($this->hasChannelsToAdd()) {
            $definitions[] = '$channelIdsToAdd: [String!]!';
        }

        return $definitions;
    }

    /**
     * Build GraphQL mutation fragments for channel updates.
     */
    public function buildMutationFragments(string $entityType, string $entityIdVar) : array
    {
        $mutations = [];

        // Remove channels first (order matters to avoid conflicts when adding/removing in same operation)
        if ($this->hasChannelsToRemove()) {
            $mutations[] = "removeChannels: removeChannelsFrom{$entityType}(id: {$entityIdVar}, input: { channelIds: \$channelIdsToRemove }) {
                    id
                }";
        }

        // Add channels second (for future use)
        if ($this->hasChannelsToAdd()) {
            $mutations[] = "addChannels: addChannelsTo{$entityType}(id: {$entityIdVar}, input: { channelIds: \$channelIdsToAdd }) {
                    id
                }";
        }

        return $mutations;
    }

    /**
     * Determine if the entity ID variable is needed.
     */
    public function needsEntityIdVariable() : bool
    {
        return true;
    }
}
