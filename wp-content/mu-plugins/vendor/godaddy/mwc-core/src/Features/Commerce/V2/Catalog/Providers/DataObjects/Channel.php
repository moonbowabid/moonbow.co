<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Represents a Channel in the Commerce Catalog v2 API.
 */
class Channel extends AbstractDataObject
{
    /** @var string Channel identifier */
    public string $channelId;

    /**
     * Creates a new Channel data object.
     *
     * @param array{
     *     channelId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
