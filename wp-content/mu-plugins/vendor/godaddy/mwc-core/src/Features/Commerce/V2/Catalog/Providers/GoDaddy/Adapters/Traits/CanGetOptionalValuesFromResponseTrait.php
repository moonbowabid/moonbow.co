<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Trait for getting optional values from GraphQL response data.
 */
trait CanGetOptionalValuesFromResponseTrait
{
    /**
     * Gets an optional string value from response data.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return string|null
     */
    protected function getOptionalStringFromResponse(array $data, string $key) : ?string
    {
        $value = ArrayHelper::get($data, $key);

        return $value !== null ? TypeHelper::string($value, '') : null;
    }

    /**
     * Gets an optional float value from response data.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return float|null
     */
    protected function getOptionalFloatFromResponse(array $data, string $key) : ?float
    {
        $value = ArrayHelper::get($data, $key);

        return $value !== null ? TypeHelper::float($value, 0.0) : null;
    }

    /**
     * Gets an optional int value from response data.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return int|null
     */
    protected function getOptionalIntFromResponse(array $data, string $key) : ?int
    {
        $value = ArrayHelper::get($data, $key);

        return $value !== null ? TypeHelper::int($value, 0) : null;
    }
}
