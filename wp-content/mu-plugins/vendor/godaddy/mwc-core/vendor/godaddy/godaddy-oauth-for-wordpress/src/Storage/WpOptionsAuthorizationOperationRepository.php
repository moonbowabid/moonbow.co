<?php

namespace GoDaddy\WordPress\OAuth\Storage;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\OAuth\Client\Models\AuthorizationOperation;
use GoDaddy\WordPress\OAuth\Storage\Contracts\AuthorizationOperationRepositoryContract;

/**
 * WordPress Options Authorization Operation Repository.
 *
 * Stores pending OAuth authorization data using WordPress options.
 */
class WpOptionsAuthorizationOperationRepository implements AuthorizationOperationRepositoryContract
{
    /**
     * Option name for storing pending authorization data.
     */
    private const AUTHORIZATION_OPERATION_OPTION = 'gd_oauth_authorization_operation';

    /**
     * Get the stored authorization operation.
     *
     * @return AuthorizationOperation|null
     */
    public function get() : ?AuthorizationOperation
    {
        $data = get_option(self::AUTHORIZATION_OPERATION_OPTION);

        if (! is_array($data)) {
            return null;
        }

        $codeVerifier = $data['code_verifier'] ?? null;
        $state = $data['state'] ?? null;
        $createdAt = $data['created_at'] ?? null;

        if (! is_string($codeVerifier) || ! is_string($state) || ! is_int($createdAt)) {
            return null;
        }

        return AuthorizationOperation::fromArray(TypeHelper::arrayOfStringsAsKeys($data));
    }

    /**
     * Save an authorization operation.
     *
     * @param AuthorizationOperation $operation The authorization operation to save
     * @return void
     */
    public function save(AuthorizationOperation $operation) : void
    {
        update_option(self::AUTHORIZATION_OPERATION_OPTION, $operation->toArray(), false);
    }

    /**
     * Delete the stored authorization operation.
     *
     * @return void
     */
    public function delete() : void
    {
        delete_option(self::AUTHORIZATION_OPERATION_OPTION);
    }
}
