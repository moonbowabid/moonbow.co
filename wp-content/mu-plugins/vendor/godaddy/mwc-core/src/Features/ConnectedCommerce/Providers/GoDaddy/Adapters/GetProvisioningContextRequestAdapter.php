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
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\GetProvisioningContextInput;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\DataObjects\ProvisioningContext;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Operations\GetProvisioningContextQuery;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Requests\ProvisioningRequest;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Traits\CanBuildProvisioningContextTrait;

/**
 * Adapter for retrieving a provisioning context by ID.
 *
 * @method static static getNewInstance(GetProvisioningContextInput $input)
 */
class GetProvisioningContextRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanBuildProvisioningContextTrait;
    use CanGetNewInstanceTrait;

    protected GetProvisioningContextInput $input;

    public function __construct(GetProvisioningContextInput $input)
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
     * Gets the GetProvisioningContextQuery with variables.
     */
    protected function getOperation() : GraphQLOperationContract
    {
        return (new GetProvisioningContextQuery())
            ->setVariables([
                'input' => [
                    'contextId' => $this->input->contextId,
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
            ArrayHelper::get($body, 'data.getProvisioningContext.provisioningContext'),
            []
        );

        return $this->buildProvisioningContext($data);
    }
}
