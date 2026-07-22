<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Operations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * Defines the GraphQL mutation to connect an existing site to Commerce.
 */
class ConnectExistingSiteMutation extends AbstractGraphQLOperation
{
    protected $operation = 'mutation ConnectExistingSite($input: ConnectExistingSiteInput!) {
        connectExistingSite(input: $input) {
            provisioningContext {
                contextId
                customerId
                businessId
                storeId
                channelId
                provisioning {
                    status
                    message
                }
            }
        }
    }';

    protected $operationType = 'mutation';
}
