<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\EligibleApiVersion\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Data object containing the eligibleVersion response from the API.
 */
class EligibleApiVersionResponse extends AbstractDataObject
{
    /** @var string either "v1" or "v2" */
    public string $eligibleVersion;

    /** @var int|null timestamp this result expires */
    public ?int $expiresAt = null;

    /** @var int|null timestamp of the last API request to get this response */
    public ?int $lastCheckedAt = null;

    /**
     * @param array{
     *      eligibleVersion: string,
     *      expiresAt?: int|null,
     *      lastCheckedAt?: int|null,
     *  } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    public function isEligibleForV2() : bool
    {
        return $this->eligibleVersion === 'v2';
    }

    /**
     * Determines whether this result has expired.
     *
     * @return bool
     */
    public function isExpired() : bool
    {
        if (empty($this->expiresAt)) {
            return false;
        }

        return $this->expiresAt < time();
    }
}
