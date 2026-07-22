<?php

namespace GoDaddy\WordPress\OAuth\Storage\Contracts;

use GoDaddy\WordPress\OAuth\Client\Models\AuthorizationOperation;

/**
 * Contract for authorization operation repositories.
 *
 * Defines methods for storing and managing pending OAuth authorization
 * data during the PKCE flow.
 */
interface AuthorizationOperationRepositoryContract
{
    /**
     * Get the stored authorization operation.
     *
     * Returns the authorization operation stored during the authorization
     * initiation, needed to complete the OAuth callback.
     *
     * @return AuthorizationOperation|null
     */
    public function get() : ?AuthorizationOperation;

    /**
     * Save an authorization operation.
     *
     * Stores the authorization operation during authorization initiation
     * for later verification during the callback.
     *
     * @param AuthorizationOperation $operation The authorization operation to save
     * @return void
     */
    public function save(AuthorizationOperation $operation) : void;

    /**
     * Delete the stored authorization operation.
     *
     * @return void
     */
    public function delete() : void;
}
