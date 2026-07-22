<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\NotUniqueException;

/**
 * Trait for handling GraphQL errors in response data.
 */
trait CanHandleGraphQLErrorsTrait
{
    /**
     * Throws an exception on error responses, including GraphQL errors.
     *
     * @param ResponseContract $response
     * @return void
     * @throws CommerceExceptionContract
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        // Handle GraphQL errors first using the trait
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $this->throwIfGraphQLErrors($responseBody);

        // Call the parent method to handle standard error responses
        parent::throwIfIsErrorResponse($response);
    }

    /**
     * Throws appropriate exception if GraphQL errors are present in response body.
     *
     * @param array<string, mixed> $responseBody
     * @return void
     * @throws CommerceExceptionContract
     */
    protected function throwIfGraphQLErrors(array $responseBody) : void
    {
        $errors = ArrayHelper::getArrayValueForKey($responseBody, 'errors', []);
        if (empty($errors)) {
            return;
        }

        $errorMessages = array_map(function ($error) {
            return ArrayHelper::get($error, 'message', 'Unknown error');
        }, $errors);

        $errorMessage = 'GraphQL errors: '.implode(', ', $errorMessages);

        if ($this->isNotUniqueErrorResponse($errors)) {
            throw new NotUniqueException($errorMessage);
        } else {
            throw new GatewayRequestException($errorMessage);
        }
    }

    /**
     * Checks if any error has CONFLICT extension code indicating a not unique error.
     *
     * @param array<mixed> $errors
     * @return bool
     */
    protected function isNotUniqueErrorResponse(array $errors) : bool
    {
        foreach ($errors as $error) {
            $errorCode = ArrayHelper::get($error, 'extensions.code');
            if ($errorCode === 'CONFLICT') {
                return true;
            }
        }

        return false;
    }
}
