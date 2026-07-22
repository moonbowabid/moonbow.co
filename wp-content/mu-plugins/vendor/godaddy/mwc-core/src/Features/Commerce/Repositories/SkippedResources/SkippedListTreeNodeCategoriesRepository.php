<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;

class SkippedListTreeNodeCategoriesRepository extends AbstractSkippedResourcesRepository
{
    protected string $resourceType = CommerceResourceTypes::ListTreeNode;
}
