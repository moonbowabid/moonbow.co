<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ProductAttributeMappingService
{
    use CanGetNewInstanceTrait;

    /** @var string */
    protected const META_KEY_ATTRIBUTE_SLUG_TO_REMOTE_NAME_MAP = '_gd_wc_attribute_slug_to_remote_name_map';

    /** @var string */
    protected const META_KEY_ATTRIBUTE_REMOTE_NAME_TO_SLUG_MAP = '_gd_wc_attribute_remote_name_to_slug_map';

    /** @var string */
    protected const META_KEY_ATTRIBUTE_VALUE_SLUG_TO_REMOTE_NAME_MAP = '_gd_wc_attribute_value_slug_to_remote_name_map';

    /** @var array<string, string> */
    protected array $localAttributeSlugToRemoteNameMap = [];

    /** @var array<string, string> */
    protected array $remoteAttributeNameToLocalSlugMap = [];

    /** @var array<string, array<string, string>> */
    protected array $localAttributeValueSlugToRemoteNameMap = [];

    public static function for(Product $product) : self
    {
        $maps = static::getAttributeMapsForProductId((int) $product->getId());

        // We try to load attribute maps from parent products if no data is found, because
        // variations created in WooCommerce won't have attribute maps stored as metadata
        // when they are about to be created in the Commerce platform, but the parent product
        // may have the necessary maps.
        if ($product->getType() === 'variation' && ! array_filter($maps)) {
            $maps = static::getAttributeMapsForProductId((int) $product->getParentId());
        }

        return new self(...$maps);
    }

    /**
     * @param int $productId
     * @return array{
     *     0: array<string, string>,
     *     1: array<string, string>,
     *     2: array<string, array<string, string>>,
     * }
     */
    protected static function getAttributeMapsForProductId(int $productId) : array
    {
        return [
            TypeHelper::arrayOfStringsWithStringsAsKeys(get_post_meta($productId, static::META_KEY_ATTRIBUTE_SLUG_TO_REMOTE_NAME_MAP, true)),
            TypeHelper::arrayOfStringsWithStringsAsKeys(get_post_meta($productId, static::META_KEY_ATTRIBUTE_REMOTE_NAME_TO_SLUG_MAP, true)),
            TypeHelper::arrayOfArraysOfStringsWithStringsAsKeys(get_post_meta($productId, static::META_KEY_ATTRIBUTE_VALUE_SLUG_TO_REMOTE_NAME_MAP, true)),
        ];
    }

    /**
     * @param array<string, string> $localAttributeSlugToRemoteNameMap
     * @param array<string, string> $remoteAttributeNameToLocalSlugMap
     * @param array<string, array<string, string>> $localAttributeValueSlugToRemoteNameMap
     */
    public function __construct(
        array $localAttributeSlugToRemoteNameMap = [],
        array $remoteAttributeNameToLocalSlugMap = [],
        array $localAttributeValueSlugToRemoteNameMap = []
    ) {
        $this->localAttributeSlugToRemoteNameMap = $localAttributeSlugToRemoteNameMap;
        $this->remoteAttributeNameToLocalSlugMap = $remoteAttributeNameToLocalSlugMap;
        $this->localAttributeValueSlugToRemoteNameMap = $localAttributeValueSlugToRemoteNameMap;
    }

    public function addAttributeMapping(string $localAttributeSlug, string $remoteAttributeName) : void
    {
        $this->localAttributeSlugToRemoteNameMap[$localAttributeSlug] = $remoteAttributeName;
        $this->remoteAttributeNameToLocalSlugMap[$remoteAttributeName] = $localAttributeSlug;
    }

    public function addAttributeValueMapping(string $localAttributeSlug, string $localValueSlug, string $remoteValueName) : void
    {
        $this->localAttributeValueSlugToRemoteNameMap[$localAttributeSlug][$localValueSlug] = $remoteValueName;
    }

    /**
     * @return array<string, mixed>
     */
    public function asMetadata() : array
    {
        return [
            self::META_KEY_ATTRIBUTE_SLUG_TO_REMOTE_NAME_MAP       => $this->localAttributeSlugToRemoteNameMap,
            self::META_KEY_ATTRIBUTE_REMOTE_NAME_TO_SLUG_MAP       => $this->remoteAttributeNameToLocalSlugMap,
            self::META_KEY_ATTRIBUTE_VALUE_SLUG_TO_REMOTE_NAME_MAP => $this->localAttributeValueSlugToRemoteNameMap,
        ];
    }

    public function getLocalAttributeSlugForRemoteAttributeName(string $remoteAttributeName) : string
    {
        return $this->remoteAttributeNameToLocalSlugMap[$remoteAttributeName] ?? $remoteAttributeName;
    }

    public function getRemoteAttributeNameForLocalAttributeSlug(string $localAttributeSlug) : string
    {
        return $this->localAttributeSlugToRemoteNameMap[$localAttributeSlug] ?? $localAttributeSlug;
    }

    public function getRemoteAttributeValueNameForLocalAttributeValueSlug(string $localAttributeSlug, string $localValueSlug) : string
    {
        return $this->localAttributeValueSlugToRemoteNameMap[$localAttributeSlug][$localValueSlug] ?? $localValueSlug;
    }
}
