<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\MediaObject;

/**
 * Adapter to convert media object data from GraphQL response.
 */
class MediaObjectAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> A graphQL node containing media object structure */
    protected array $source;

    /**
     * MediaObjectAdapter constructor.
     *
     * @param array<string, mixed> $source Node containing media object structure
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts from GraphQL response format to a media object.
     *
     * @return MediaObject|null
     */
    public function convertFromSource() : ?MediaObject
    {
        $id = ArrayHelper::getStringValueForKey($this->source, 'id', '');
        $url = ArrayHelper::getStringValueForKey($this->source, 'url', '');

        if (! $id || ! $url) {
            return null;
        }

        return new MediaObject([
            'id'       => $id,
            'url'      => $url,
            'type'     => ArrayHelper::getStringValueForKey($this->source, 'type', 'IMAGE'),
            'name'     => ArrayHelper::getStringValueForKey($this->source, 'name'),
            'label'    => ArrayHelper::getStringValueForKey($this->source, 'label'),
            'position' => ($positionValue = ArrayHelper::get($this->source, 'position')) !== null ? TypeHelper::int($positionValue, 0) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function convertToSource() : array
    {
        // no-op.
        return [];
    }
}
