<?php

namespace GoDaddy\WordPress\OAuth\Storage\Contracts;

use GoDaddy\WordPress\OAuth\Client\Models\AccessToken;

/**
 * Contract for token storage repositories.
 *
 * Defines methods for storing, retrieving, and managing OAuth tokens.
 */
interface TokenRepositoryContract
{
    /**
     * Get the stored access token.
     *
     * @return AccessToken|null The stored token or null if not found
     */
    public function get() : ?AccessToken;

    /**
     * Save an access token.
     *
     * @param AccessToken $token The token to save
     * @return void
     */
    public function save(AccessToken $token) : void;

    /**
     * Delete the stored access token.
     *
     * @return void
     */
    public function delete() : void;

    /**
     * Check if a token exists.
     *
     * @return bool True if a token is stored, false otherwise
     */
    public function has() : bool;
}
