<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Represents a metafield in the Commerce Catalog v2 API.
 */
class Metafield extends AbstractDataObject
{
    /** @var string Metafield namespace (e.g., "commerce-apps") */
    public string $namespace;

    /** @var string Metafield key identifier */
    public string $key;

    /** @var string|null Metafield value (may be JSON-encoded) */
    public ?string $value = null;

    /** @var string|null Metafield type */
    public ?string $type = null;

    /**
     * @param array{
     *     namespace: string,
     *     key: string,
     *     value?: string|null,
     *     type?: string|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
