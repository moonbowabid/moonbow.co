<?php

namespace GoDaddy\WordPress\MWC\Core\Auth\API\Controllers\OAuth;

use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Admin\GoDaddyStorePage;
use Throwable;
use WP_Error;
use WP_REST_Response;

/**
 * REST controller for three-legged OAuth tokens.
 */
class TokenController extends AbstractController implements ComponentContract
{
    /** @var string REST error code returned when the OAuth token provider cannot be resolved. */
    public const ERROR_CODE_OAUTH_UNAVAILABLE = 'mwc_core_oauth_provider_unavailable';

    /** @var string */
    protected $route = 'oauth/token';

    /**
     * Initializes the controller.
     */
    public function load() : void
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'createItem'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Retrieves the three-legged OAuth token.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function createItem()
    {
        try {
            /** @var ThreeLeggedOAuthTokenProviderContract $authProvider */
            $authProvider = ContainerFactory::getInstance()->getSharedContainer()->get(ThreeLeggedOAuthTokenProviderContract::class);
        } catch (Throwable $throwable) {
            return rest_ensure_response(new WP_Error(
                self::ERROR_CODE_OAUTH_UNAVAILABLE,
                __('The OAuth token provider is not available.', 'mwc-core'),
                ['status' => 500]
            ));
        }

        try {
            $response = $authProvider->getCredentials()->toArray();
        } catch (Throwable $throwable) {
            $response = new WP_Error($throwable->getCode() ?: 500, $throwable->getMessage(), [
                'status' => $throwable->getCode() ?: 500,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Determines if the current user has permissions to issue requests to create items.
     *
     * @return bool
     */
    public function createItemPermissionsCheck() : bool
    {
        return current_user_can(GoDaddyStorePage::CAPABILITY);
    }

    /**
     * Gets the item schema.
     *
     * @return array<string, mixed>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'oauth-token',
            'type'       => 'object',
            'properties' => [
                'accessToken' => [
                    'description' => __('The access token string as issued by the authorization server.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'expiresIn' => [
                    'description' => __('Number of seconds to expiration of the access token.', 'mwc-core'),
                    'type'        => 'integer',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'scope' => [
                    'description' => __('The scope the token granted.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'tokenType' => [
                    'description' => __('The type of token this is.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
