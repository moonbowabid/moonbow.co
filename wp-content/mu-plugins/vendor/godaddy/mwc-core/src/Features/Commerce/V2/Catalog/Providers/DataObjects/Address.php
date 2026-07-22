<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Represents an address in the Commerce Catalog v2 API.
 */
class Address extends AbstractDataObject
{
    /** @var string The first line of the address */
    public string $addressLine1;

    /** @var string|null The second line of the address */
    public ?string $addressLine2 = null;

    /** @var string|null The third line of the address */
    public ?string $addressLine3 = null;

    /** @var string The country code of the address */
    public string $countryCode;

    /** @var string|null The postal code */
    public ?string $postalCode = null;

    /** @var string|null The first administrative area */
    public ?string $adminArea1 = null;

    /** @var string|null The second administrative area */
    public ?string $adminArea2 = null;

    /** @var string|null The third administrative area */
    public ?string $adminArea3 = null;

    /** @var string|null The fourth administrative area */
    public ?string $adminArea4 = null;

    // @TODO address details when we start writing inventory data

    /**
     * Creates a new address data object.
     *
     * @param array{
     *     addressLine1: string,
     *     addressLine2?: string|null,
     *     addressLine3?: string|null,
     *     countryCode: string,
     *     postalCode?: string|null,
     *     adminArea1?: string|null,
     *     adminArea2?: string|null,
     *     adminArea3?: string|null,
     *     adminArea4?: string|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
