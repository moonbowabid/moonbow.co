<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ConnectExistingSiteInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Operations\ConnectExistingSiteMutation;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Requests\ProvisioningRequest;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Traits\CanBuildProvisioningContextTrait;

/**
 * Adapter for connecting an existing site to Commerce.
 *
 * @method static static getNewInstance(ConnectExistingSiteInput $input)
 */
class ConnectExistingSiteRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanBuildProvisioningContextTrait;
    use CanGetNewInstanceTrait;

    protected ConnectExistingSiteInput $input;

    public function __construct(ConnectExistingSiteInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CredentialsCreateFailedException
     * @throws ContainerException
     */
    public function convertFromSource() : RequestContract
    {
        return ProvisioningRequest::withAuth($this->getOperation())
            ->setMethod('post');
    }

    /**
     * Gets the ConnectExistingSiteMutation with variables.
     */
    protected function getOperation() : GraphQLOperationContract
    {
        return (new ConnectExistingSiteMutation())
            ->setVariables([
                'input' => [
                    'businessId' => $this->input->businessId,
                    'storeId'    => $this->input->storeId,
                    'siteUid'    => $this->input->siteUid,
                ],
            ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function convertResponse(ResponseContract $response) : ProvisioningContext
    {
        /** @var array<string, mixed> $body */
        $body = TypeHelper::array($response->getBody(), []);

        /** @var array<string, mixed> $data */
        $data = TypeHelper::array(
            ArrayHelper::get($body, 'data.connectExistingSite.provisioningContext'),
            []
        );

        return $this->buildProvisioningContext($data);
    }
}
