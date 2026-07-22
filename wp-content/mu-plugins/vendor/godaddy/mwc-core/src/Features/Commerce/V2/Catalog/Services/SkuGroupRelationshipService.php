<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders\SkuGroupRelationshipUpdateBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuGroupRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuGroup;

class SkuGroupRelationshipService
{
    protected CommerceContextContract $commerceContextContract;
    protected SkuGroupRelationshipUpdateBuilder $skuGroupRelationshipUpdateBuilder;
    protected CatalogProviderContract $catalogProvider;

    public function __construct(
        CommerceContextContract $commerceContextContract,
        CatalogProviderContract $catalogProvider,
        SkuGroupRelationshipUpdateBuilder $skuGroupRelationshipUpdateBuilder
    ) {
        $this->commerceContextContract = $commerceContextContract;
        $this->catalogProvider = $catalogProvider;
        $this->skuGroupRelationshipUpdateBuilder = $skuGroupRelationshipUpdateBuilder;
    }

    /**
     * Update relationships for a SKU Group if needed.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param SkuGroup $skuGroup
     * @return void
     * @throws CommerceExceptionContract
     */
    public function maybeUpdateRelationships(CreateOrUpdateProductOperationContract $operation, SkuGroup $skuGroup) : void
    {
        $updates = $this->skuGroupRelationshipUpdateBuilder->build($operation, $skuGroup);
        if (! $updates->hasUpdates()) {
            return;
        }

        $input = $this->getRelationshipsInput($skuGroup, $updates);

        $this->catalogProvider->skuGroups()->updateRelationships($input);
    }

    /**
     * Get input for SKU Group relationship updates.
     *
     * @param SkuGroup $skuGroup
     * @param RelationshipUpdates $updates
     * @return UpdateSkuGroupRelationshipsInput
     * @throws MissingProductRemoteIdException
     */
    protected function getRelationshipsInput(SkuGroup $skuGroup, RelationshipUpdates $updates) : UpdateSkuGroupRelationshipsInput
    {
        if (! $skuGroup->id) {
            throw new MissingProductRemoteIdException('SKU Group ID is required for relationship updates.');
        }

        return new UpdateSkuGroupRelationshipsInput([
            'storeId'    => $this->commerceContextContract->getStoreId(),
            'skuGroupId' => $skuGroup->id,
            'updates'    => $updates,
        ]);
    }
}
