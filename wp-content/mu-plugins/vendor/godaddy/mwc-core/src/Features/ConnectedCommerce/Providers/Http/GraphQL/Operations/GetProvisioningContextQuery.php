<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Providers\Http\GraphQL\Operations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * Defines the GraphQL query to retrieve a provisioning context.
 */
class GetProvisioningContextQuery extends AbstractGraphQLOperation
{
    protected $operation = 'query GetProvisioningContext($input: GetProvisioningContextInput!) {
        getProvisioningContext(input: $input) {
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
}
