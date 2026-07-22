<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Builders\SkuRelationshipUpdateBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates\RelationshipUpdates;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;

class SkuRelationshipService
{
    protected CommerceContextContract $commerceContextContract;
    protected SkuRelationshipUpdateBuilder $skuRelationshipUpdateBuilder;
    protected CatalogProviderContract $catalogProvider;

    public function __construct(
        CommerceContextContract $commerceContextContract,
        CatalogProviderContract $catalogProvider,
        SkuRelationshipUpdateBuilder $skuRelationshipUpdateBuilder
    ) {
        $this->commerceContextContract = $commerceContextContract;
        $this->catalogProvider = $catalogProvider;
        $this->skuRelationshipUpdateBuilder = $skuRelationshipUpdateBuilder;
    }

    /**
     * @param CreateOrUpdateProductOperationContract $operation
     * @param Sku $sku
     * @return void
     * @throws MissingProductRemoteIdException|CommerceExceptionContract
     */
    public function maybeUpdateRelationships(CreateOrUpdateProductOperationContract $operation, Sku $sku) : void
    {
        $updates = $this->skuRelationshipUpdateBuilder->build($operation, $sku);
        if (! $updates->hasUpdates()) {
            return;
        }

        $input = $this->getRelationshipsInput($sku, $updates);

        $this->catalogProvider->skus()->updateRelationships($input);
    }

    /**
     * @param Sku $sku
     * @param RelationshipUpdates $updates
     * @return UpdateSkuRelationshipsInput
     * @throws MissingProductRemoteIdException
     */
    protected function getRelationshipsInput(Sku $sku, RelationshipUpdates $updates) : UpdateSkuRelationshipsInput
    {
        if (! $sku->id) {
            throw new MissingProductRemoteIdException('SKU must have an ID to update relationships.');
        }

        return new UpdateSkuRelationshipsInput([
            'storeId' => $this->commerceContextContract->getStoreId(),
            'skuId'   => $sku->id,
            'updates' => $updates,
        ]);
    }
}
