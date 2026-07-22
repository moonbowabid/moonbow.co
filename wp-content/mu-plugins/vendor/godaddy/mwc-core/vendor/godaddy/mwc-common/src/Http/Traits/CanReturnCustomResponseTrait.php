<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\Exceptions\InvalidMethodException;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;

/**
 * Allows PHPStan and IDEs to understand the concrete type of the {@see ResponseContract} instance
 * returned by {@see Request::send()}.
 *
 * Classes that define a custom value for the {@see Request::$responseClass} property are
 * encouraged to use this trait.
 *
 * @template T of ResponseContract
 * @phpstan-require-extends Request
 */
trait CanReturnCustomResponseTrait
{
    /**
     * {@inheritDoc}
     *
     * @return T
     * @throws InvalidUrlException|InvalidMethodException|Exception
     */
    public function send()
    {
        return $this->performRequest($this->responseClass);
    }
}
