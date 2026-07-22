<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

class Attribute extends AbstractDataObject
{
    /** @var string|null The attribute UUID (will be set on reads) */
    public ?string $id = null;

    /** @var string The attribute name (lower case) */
    public string $name;

    /** @var string The attribute label, essentially a display name */
    public string $label;

    /** @var int The position of the attribute in the list */
    public int $position = 0;

    /** @var AttributeValue[] The attribute values for this attribute */
    public array $values = [];

    /**
     * @param array{
     *     id?: string|null,
     *     name: string,
     *     label: string,
     *     position?: int,
     *     values?: AttributeValue[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
