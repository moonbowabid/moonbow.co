<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\API\Controllers\Store;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Admin\GoDaddyStorePage;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ConnectExistingSiteInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Services\Contracts\ProvisioningServiceContract;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST controller for connecting a site to a Commerce store.
 */
class ConnectionController extends AbstractController implements ComponentContract
{
    /** @var string */
    protected $route = 'commerce/store/connection';

    protected ProvisioningServiceContract $provisioningService;

    /**
     * Constructor.
     */
    public function __construct(ProvisioningServiceContract $provisioningService)
    {
        $this->provisioningService = $provisioningService;
    }

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
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'createItem'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
                'args'                => [
                    'businessId' => [
                        'required'          => true,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                    'storeId' => [
                        'required'          => true,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                ],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Gets the current store connection.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getItem()
    {
        try {
            $contextId = $this->provisioningService->getProvisioningContextId();

            if (! $contextId) {
                return rest_ensure_response([
                    'contextId'  => null,
                    'status'     => 'NOT_FOUND',
                    'businessId' => null,
                    'storeId'    => null,
                    'channelId'  => null,
                    'customerId' => null,
                ]);
            }

            $provisioningContext = $this->provisioningService->getProvisioningContext(
                new GetProvisioningContextInput(['contextId' => $contextId])
            );

            $response = $this->buildConnectionResponse($provisioningContext);
        } catch (CommerceExceptionContract $exception) {
            $response = new WP_Error($exception->getErrorCode(), $exception->getMessage(), [
                'status' => 500,
            ]);
        } catch (Exception $exception) {
            $response = new WP_Error($exception->getCode() ?: 500, $exception->getMessage(), [
                'status' => $exception->getCode() ?: 500,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Determines if the current user has permissions to issue requests to get items.
     *
     * @return bool
     */
    public function getItemsPermissionsCheck() : bool
    {
        return current_user_can(GoDaddyStorePage::CAPABILITY);
    }

    /**
     * Connects the current site to a Commerce store.
     *
     * @param WP_REST_Request<array<string, mixed>> $request
     * @return WP_REST_Response|WP_Error
     */
    public function createItem(WP_REST_Request $request)
    {
        try {
            $provisioningContext = $this->provisioningService->connectExistingSite(
                new ConnectExistingSiteInput([
                    'businessId' => TypeHelper::string($request->get_param('businessId'), ''),
                    'storeId'    => TypeHelper::string($request->get_param('storeId'), ''),
                    'siteUid'    => $this->getSiteUid(),
                ])
            );

            $response = $this->buildConnectionResponse($provisioningContext);
        } catch (CommerceExceptionContract $exception) {
            $response = new WP_Error($exception->getErrorCode(), $exception->getMessage(), [
                'status' => 500,
            ]);
        } catch (Exception $exception) {
            $response = new WP_Error($exception->getCode() ?: 500, $exception->getMessage(), [
                'status' => $exception->getCode() ?: 500,
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
     * Builds the connection response array from a provisioning context.
     *
     * @return array<string, string|null>
     */
    protected function buildConnectionResponse(ProvisioningContext $provisioningContext) : array
    {
        return [
            'contextId'  => $provisioningContext->contextId,
            'status'     => $provisioningContext->provisioningStatus,
            'businessId' => $provisioningContext->businessId,
            'storeId'    => $provisioningContext->storeId,
            'channelId'  => $provisioningContext->channelId,
            'customerId' => $provisioningContext->customerId,
        ];
    }

    /**
     * Gets the site UID from configuration.
     *
     * TODO: Replace with a platform-agnostic approach.
     */
    protected function getSiteUid() : string
    {
        return TypeHelper::string(Configuration::get('godaddy.account.uid'), '');
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
            'title'      => 'store-connection',
            'type'       => 'object',
            'properties' => [
                'contextId' => [
                    'description' => __('The provisioning context identifier.', 'mwc-core'),
                    'type'        => ['string', 'null'],
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'status' => [
                    'description' => __('The current connection status.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'businessId' => [
                    'description' => __('The business identifier.', 'mwc-core'),
                    'type'        => ['string', 'null'],
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'storeId' => [
                    'description' => __('The store identifier.', 'mwc-core'),
                    'type'        => ['string', 'null'],
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'channelId' => [
                    'description' => __('The channel identifier.', 'mwc-core'),
                    'type'        => ['string', 'null'],
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'customerId' => [
                    'description' => __('The customer identifier.', 'mwc-core'),
                    'type'        => ['string', 'null'],
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
