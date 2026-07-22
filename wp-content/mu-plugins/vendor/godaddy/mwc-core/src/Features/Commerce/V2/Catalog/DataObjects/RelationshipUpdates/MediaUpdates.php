<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

class MediaUpdates extends AbstractRelationshipUpdate
{
    /** @var string[] array of remote UUIDs to be removed */
    public array $toRemove = [];

    /** @var MediaObject[] array of objects to be added */
    public array $toAdd = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Check if there are media objects to remove.
     *
     * @return bool
     */
    public function hasMediaToRemove() : bool
    {
        return ! empty($this->toRemove);
    }

    /**
     * Check if there are media objects to add.
     *
     * @return bool
     */
    public function hasMediaToAdd() : bool
    {
        return ! empty($this->toAdd);
    }

    /**
     * Build GraphQL variables for media updates.
     *
     * @return array<string, mixed>
     */
    public function buildGraphQLVariables() : array
    {
        $variables = [];

        if ($this->hasMediaToAdd()) {
            $variables['mediaToAdd'] = $this->buildMediaObjectsInput($this->toAdd);
        }

        if ($this->hasMediaToRemove()) {
            $variables['mediaToRemove'] = $this->toRemove;
        }

        return $variables;
    }

    /**
     * Build media objects input for GraphQL mutations.
     *
     * @param MediaObject[] $mediaObjects Array of MediaObject instances
     * @return array<int, array<string, mixed>> Array of media object input data for GraphQL
     */
    protected function buildMediaObjectsInput(array $mediaObjects) : array
    {
        $result = [];
        foreach ($mediaObjects as $mediaObject) {
            // Remove 'id' field for creation input as GraphQL CreateMediaObjectInput doesn't expect it
            $data = ArrayHelper::except($mediaObject->toArray(), 'id');
            $result[] = TypeHelper::arrayOfStringsAsKeys($data);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function buildVariableDefinitions() : array
    {
        $definitions = [];

        if ($this->hasMediaToRemove()) {
            $definitions[] = '$mediaToRemove: [String!]!';
        }

        if ($this->hasMediaToAdd()) {
            $definitions[] = '$mediaToAdd: [CreateMediaObjectInput!]!';
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
        if ($this->hasMediaToRemove()) {
            $mutations[] = "removeMedia: removeMediaObjectsFrom{$entityType}(id: {$entityIdVar}, input: { mediaObjectIds: \$mediaToRemove }) {
                    id
                }";
        }

        // Add ADD mutations SECOND
        if ($this->hasMediaToAdd()) {
            $mutations[] = "addMedia: addMediaObjectsTo{$entityType}(id: {$entityIdVar}, input: { mediaObjects: \$mediaToAdd }) {
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
