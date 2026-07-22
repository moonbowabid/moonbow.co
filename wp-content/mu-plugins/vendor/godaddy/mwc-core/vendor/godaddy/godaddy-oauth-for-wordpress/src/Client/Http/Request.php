<?php

namespace GoDaddy\WordPress\OAuth\Client\Http;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request as CommonRequest;
use GoDaddy\WordPress\MWC\Common\Http\Traits\CanReturnCustomResponseTrait;
use RuntimeException;

class Request extends CommonRequest
{
    /** @use CanReturnCustomResponseTrait<Response> */
    use CanReturnCustomResponseTrait;

    /** @var class-string<Response> the type of response the request should return */
    protected $responseClass = Response::class;

    /**
     * @param array<string, mixed> $headers
     * @return string|mixed[]|null
     */
    protected function buildBody(array $headers)
    {
        if (empty($this->body)) {
            return null;
        }

        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'content-type' && is_string($value) && StringHelper::startsWith(strtolower(trim($value)), 'application/json')) {
                $encoded = json_encode($this->body);

                if ($encoded === false) {
                    throw new RuntimeException('Failed to JSON-encode request body: '.json_last_error_msg());
                }

                return $encoded;
            }
        }

        return $this->body;
    }
}
