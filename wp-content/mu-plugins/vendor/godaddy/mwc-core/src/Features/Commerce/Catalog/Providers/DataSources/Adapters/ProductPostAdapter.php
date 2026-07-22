<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductPost;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductParentIdResolverContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Contracts\ProductMapRepositoryContract;

/**
 * Adapter for converting a {@see ProductBase} DTO into a {@see ProductPost} DTO.
 *
 * Use this adapter to convert a product into an object that can be used to overlay or create a WordPress post.
 *
 * Examples:
 *
 * // to overlay the product base data to an existing WP_Post:
 * ProductPostAdapter::getNewInstance($productsMappingService)->setLocalPost((array) $wpPost)->convertToSource($productBase)->toWordPressPostObject($wpPost);
 * // to obtain an array of data that is compatible with WP_Post data (for example when interacting with WPDB):
 * ProductPostAdapter::getNewInstance($productsMappingService)->setLocalPost((array) $wpPost)->convertToSource($productBase)->toDatabaseArray();
 * // the same as above but outputs a stdClass object to match WP_Post properties as from a wp_posts row:
 * ProductPostAdapter::getNewInstance($productsMappingService)->setLocalPost((array) $wpPost)->convertToSource($productBase)->toDatabaseObject();
 */
class ProductPostAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;
    use HasStringRemoteIdentifierTrait;

    /** @var ProductMapRepositoryContract */
    protected ProductMapRepositoryContract $productMapRepository;

    /** @var ProductParentIdResolverContract */
    protected ProductParentIdResolverContract $parentIdResolver;

    /** @var array<string, mixed> the corresponding local post object in array form - used to compare against the local database */
    protected array $localPost = [];

    protected ProductPostStatusAdapter $productPostStatusAdapter;

    /**
     * Constructor.
     *
     * @param ProductMapRepositoryContract $productMapRepository
     * @param ProductParentIdResolverContract $parentIdResolver
     * @param ProductPostStatusAdapter $productPostStatusAdapter
     */
    public function __construct(
        ProductMapRepositoryContract $productMapRepository,
        ProductParentIdResolverContract $parentIdResolver,
        ProductPostStatusAdapter $productPostStatusAdapter
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->parentIdResolver = $parentIdResolver;
        $this->productPostStatusAdapter = $productPostStatusAdapter;
    }

    /**
     * Gets the local post data (in array form) to use in comparisons.
     *
     * @return array<string, mixed>
     */
    public function getLocalPost() : array
    {
        return $this->localPost;
    }

    /**
     * Sets the local post data (in array form) to use in comparisons.
     *
     * @param array<string, mixed> $value
     * @return $this
     */
    public function setLocalPost(array $value) : ProductPostAdapter
    {
        $this->localPost = $value;

        return $this;
    }

    /**
     * Converts a {@see ProductBase} DTO into a {@see ProductPost} DTO.
     *
     * @NOTE If a post is password protected then the status will be set to publish regardless of {@see ProductBase::$active} property.
     * @see ProductPost::toWordPressPost()
     * @see ProductPost::toDatabaseArray()
     *
     * @param ProductBase|null $productBase
     * @return ProductPost|null
     */
    public function convertToSource(?ProductBase $productBase = null) : ?ProductPost
    {
        if (! $productBase) {
            return null;
        }

        return new ProductPost([
            'postTitle'       => $productBase->name,
            'postContent'     => $productBase->description,
            'postDate'        => $this->convertDateFromGmt($productBase->createdAt),
            'postDateGmt'     => $productBase->createdAt,
            'postModified'    => $this->convertDateFromGmt($productBase->updatedAt),
            'postModifiedGmt' => $productBase->updatedAt,
            'postParent'      => $this->convertRemoteParentUuidToLocalParentId($productBase->parentId),
            'postStatus'      => $this->convertStatusToSource($productBase),
        ]);
    }

    /**
     * Exchanges a remote Commerce UUID for a local (WooCommerce) product parent ID.
     *
     * @param string|null $remoteParentId
     * @return int|null local parent ID
     */
    protected function convertRemoteParentUuidToLocalParentId(?string $remoteParentId) : ?int
    {
        if (empty($remoteParentId)) {
            return null;
        }

        try {
            return $this->parentIdResolver->getLocalParentId($remoteParentId);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Converts the product's status.
     *
     * @param ProductBase $productBase
     * @return string
     */
    protected function convertStatusToSource(ProductBase $productBase) : string
    {
        $localPostStatus = TypeHelper::string($this->getLocalPost()['post_status'] ?? 'draft', 'draft');

        return $this->productPostStatusAdapter->convertToSource($productBase->active, $localPostStatus);
    }

    /**
     * Converts GMT date to local date {@see get_date_from_gmt()}.
     *
     * @param string|null $date
     * @return string
     */
    protected function convertDateFromGmt(?string $date) : string
    {
        return $date ? get_date_from_gmt($date) : '';
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : void
    {
        // no-op
    }
}
