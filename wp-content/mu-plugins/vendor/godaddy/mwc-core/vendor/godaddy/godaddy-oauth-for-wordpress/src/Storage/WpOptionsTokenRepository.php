<?php

namespace GoDaddy\WordPress\OAuth\Storage;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\OAuth\Client\Models\AccessToken;
use GoDaddy\WordPress\OAuth\Storage\Contracts\TokenRepositoryContract;

/**
 * WordPress Options Token Repository.
 *
 * Stores OAuth tokens using WordPress options.
 */
class WpOptionsTokenRepository implements TokenRepositoryContract
{
    /**
     * Option name for storing the access token.
     */
    private const TOKEN_OPTION = 'gd_oauth_connection';

    /**
     * Get the stored access token.
     *
     * @return AccessToken|null The stored token or null if not found
     */
    public function get() : ?AccessToken
    {
        $data = get_option(self::TOKEN_OPTION);

        if (! is_array($data) || empty($data)) {
            return null;
        }

        if (! isset($data['access_token'])) {
            return null;
        }

        return AccessToken::fromArray(TypeHelper::arrayOfStringsAsKeys($data));
    }

    /**
     * Save an access token.
     *
     * @param AccessToken $token The token to save
     * @return void
     */
    public function save(AccessToken $token) : void
    {
        update_option(self::TOKEN_OPTION, $token->toArray(), false);
    }

    /**
     * Delete the stored access token.
     *
     * @return void
     */
    public function delete() : void
    {
        delete_option(self::TOKEN_OPTION);
    }

    /**
     * Check if a token exists.
     *
     * @return bool True if a token is stored, false otherwise
     */
    public function has() : bool
    {
        return $this->get() !== null;
    }
}
