<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Generic repository contract for managing resource ID mappings between local and remote systems.
 * It's expected that any concretes using this interface are extending {@see AbstractResourceMapRepository}, which implements
 * most of these public methods.
 */
interface ResourceMapRepositoryContract
{
    /**
     * Gets the local IDs of resources that do not yet exist in the mapping table.
     *
     * @param int $limit
     * @return int[]
     */
    public function getUnmappedLocalIds(int $limit = 50) : array;

    /**
     * Adds a new map to associate the local ID with the given remote UUID.
     *
     * @param int $localId
     * @param string $remoteId
     * @return void
     * @throws WordPressDatabaseException
     */
    public function add(int $localId, string $remoteId) : void;

    /**
     * Updates the remote ID of a row, if found by local ID, otherwise adds the map.
     *
     * Unlike {@see ResourceMapRepositoryContract::add()}, this method does not attempt
     * to write to the database if an identical map already exists.
     *
     * @param int $localId
     * @param string $remoteId
     * @return void
     * @throws WordPressDatabaseException
     */
    public function addOrUpdateRemoteId(int $localId, string $remoteId) : void;

    /**
     * Finds the remote ID of a resource by its local ID.
     *
     * @param int $localId
     * @return string|null
     */
    public function getRemoteId(int $localId) : ?string;

    /**
     * Get a collection of resource maps by the given local IDs.
     *
     * @param int[] $localIds
     * @return ResourceMapCollection
     */
    public function getMappingsByLocalIds(array $localIds) : ResourceMapCollection;

    /**
     * Get a collection of resource maps by the given remote IDs.
     *
     * @param string[] $remoteIds
     * @return ResourceMapCollection
     */
    public function getMappingsByRemoteIds(array $remoteIds) : ResourceMapCollection;

    /**
     * Finds the local ID of a resource by its remote UUID.
     *
     * @param string $remoteId
     * @return int|null
     */
    public function getLocalId(string $remoteId) : ?int;

    /**
     * Deletes a mapping row by the provided local ID.
     *
     * @param int $localId
     * @return int number of records that were deleted
     * @throws WordPressDatabaseException
     */
    public function deleteByLocalId(int $localId) : int;

    /**
     * Gets a SQL query that can be used to select all `local_id` values from the table for a specific resource type ID.
     * e.g. `SELECT local_id FROM godaddy_mwc_commerce_map_ids WHERE resource_type_id = 11`.
     *
     * @return string
     */
    public function getMappedLocalIdsForResourceTypeQuery() : string;

    /**
     * Get the resource type handled by this class.
     *
     * @return string|null
     */
    public function getResourceType() : ?string;

    /**
     * Gets the resource type ID.
     *
     * @return int|null
     */
    public function getResourceTypeId() : ?int;
}
