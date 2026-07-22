<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\BeforeLoadProductInformationEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits\CanInjectCommerceProductsIntoPostsArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineShouldPrimeProductsCacheTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineWpQueryProductPostTypeTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use stdClass;
use WP_Post;
use WP_Query;

/**
 * A handler for injecting Commerce data into WordPress product posts in WordPress queries.
 */
class ProductQueryHandler extends AbstractInterceptorHandler
{
    use CanDetermineWpQueryProductPostTypeTrait;
    use CanInjectCommerceProductsIntoPostsArrayTrait;
    use CanDetermineShouldPrimeProductsCacheTrait;

    /**
     * Constructor.
     *
     * @param ProductPostAdapter $postAdapter
     * @param BatchListProductsByLocalIdService $batchListProductsByLocalIdService
     */
    public function __construct(ProductPostAdapter $postAdapter, BatchListProductsByLocalIdService $batchListProductsByLocalIdService)
    {
        $this->postAdapter = $postAdapter;
        $this->batchListProductsByLocalIdService = $batchListProductsByLocalIdService;
    }

    /**
     * Injects Commerce data into the given posts.
     *
     * @param array<mixed> $args
     * @return WP_Post[]|stdClass[]
     */
    public function run(...$args) : array
    {
        /** @var WP_Post[]|stdClass[] $posts */
        $posts = TypeHelper::array($args[0] ?? [], []);
        /** @var mixed|null $wpQuery */
        $wpQuery = $args[1] ?? null;

        if (empty($posts)) {
            return $posts;
        }

        if (! $wpQuery instanceof WP_Query) {
            return $posts;
        }

        if (! $this->isProductQuery($wpQuery)) {
            return $posts;
        }

        if ($this->shouldReadProducts()) {
            return $this->injectCommerceData($posts);
        } elseif (static::shouldPrimeProductsCache()) {
            return $this->maybeBroadcastBeforeLoadProductInformationEvent($posts);
        }

        return $posts;
    }

    /**
     * Prime inventory cache for products in the current query.
     *
     * @param WP_Post[]|stdClass[] $posts
     * @return WP_Post[]|stdClass[]
     */
    public function maybeBroadcastBeforeLoadProductInformationEvent(array $posts) : array
    {
        $productIds = $this->getProductIds($posts);

        if (empty($productIds)) {
            return $posts;
        }

        Events::broadcast(
            BeforeLoadProductInformationEvent::withLocalIds($productIds)
                ->setWithVariants($this->shouldQueryVariants())
        );

        return $posts;
    }

    /**
     * Extract product IDs from posts.
     *
     * @param mixed[] $posts
     * @return int[]
     */
    protected function getProductIds(array $posts) : array
    {
        $productIds = [];

        foreach ($posts as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }

            $productIds[] = $post->ID;
        }

        return array_unique($productIds);
    }
}
