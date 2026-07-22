<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeNode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\QueryListTreeNodesInput;

/**
 * Service for querying ListTreeNode data from the API.
 */
class ListTreeNodeQueryService
{
    protected CatalogProviderContract $catalogProvider;
    protected CommerceContextContract $commerceContext;

    public function __construct(CatalogProviderContract $catalogProvider, CommerceContextContract $commerceContext)
    {
        $this->catalogProvider = $catalogProvider;
        $this->commerceContext = $commerceContext;
    }

    /**
     * Fetches the ListTreeNode associated with a given list ID.
     *
     * @param string $listId
     * @return ?ListTreeNode
     * @throws CommerceExceptionContract
     */
    public function getByListId(string $listId) : ?ListTreeNode
    {
        return $this->catalogProvider->listTreeNodes()->queryByListId(
            new QueryListTreeNodesInput([
                'listId'  => $listId,
                'storeId' => $this->commerceContext->getStoreId(),
            ])
        );
    }
}
