<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill;

use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CategoryListTreeBackfill\Interceptors\InitiateCategoryListTreeBackfillInterceptor;

/**
 * Feature responsible for backfilling ListTree and ListTreeNode resources for categories that already have List mappings.
 */
class CommerceCategoryListTreeBackfill extends AbstractFeature
{
    use HasComponentsFromContainerTrait;

    /** @var class-string[] */
    protected array $componentClasses = [
        InitiateCategoryListTreeBackfillInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce_category_list_tree_backfill';
    }

    /**
     * {@inheritDoc}
     *
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }
}
