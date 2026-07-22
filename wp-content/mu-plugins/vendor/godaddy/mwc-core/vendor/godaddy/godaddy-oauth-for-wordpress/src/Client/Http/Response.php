<?php

namespace GoDaddy\WordPress\OAuth\Client\Http;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response as CommonResponse;
use GoDaddy\WordPress\OAuth\Client\Exceptions\HttpException;

class Response extends CommonResponse
{
    /**
     * @return $this
     * @throws HttpException
     */
    public function throw()
    {
        if ($this->isError()) {
            throw $this->toException();
        }

        return $this;
    }

    protected function toException() : HttpException
    {
        $exception = new HttpException($this->getErrorMessageWithFallback(), null, $this->getStatus() ?: 500);

        if ($errorCode = $this->getErrorCode()) {
            $exception->setErrorCode($errorCode);
        }

        return $exception;
    }

    /**
     * Generates an error message from the given response.
     */
    protected function getErrorMessageWithFallback() : string
    {
        if ($errorMessage = $this->getErrorMessage()) {
            return $errorMessage;
        }

        if ($status = $this->getStatus()) {
            return "Request Error. The server responded with status: {$status}";
        }

        return 'Request Error. The server responded with an unknown status.';
    }

    /**
     * Gets the error code.
     *
     * @return string|null
     */
    public function getErrorCode() : ?string
    {
        if (! $this->isError()) {
            return null;
        }

        if (is_callable([$this->response, 'get_error_code'])) {
            return TypeHelper::stringOrNull($this->response->get_error_code());
        }

        $body = $this->getBody();

        foreach (['code'] as $key) {
            if ($message = TypeHelper::stringOrNull(ArrayHelper::get($body, $key))) {
                return $message;
            }
        }

        return null;
    }
}
