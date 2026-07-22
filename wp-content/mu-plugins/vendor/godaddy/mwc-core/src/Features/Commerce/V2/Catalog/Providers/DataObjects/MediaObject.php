<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Media object reference data object.
 */
class MediaObject extends AbstractDataObject
{
    /** @var string|null Globally-unique ID */
    public ?string $id = null;

    /** @var string Type of media object - currently only 'IMAGE' is supported */
    public string $type = 'IMAGE';

    /** @var string URL of the media object */
    public string $url;

    /** @var string|null Name of the media object (WordPress post_name/slug) */
    public ?string $name = null;

    /** @var string|null Label/title of the media object (WordPress post_title) */
    public ?string $label = null;

    /** @var int|null Position/order of the media object */
    public ?int $position = null;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     id?: string|null,
     *     type?: string,
     *     url: string,
     *     name?: string|null,
     *     label?: string|null,
     *     position?: int|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
