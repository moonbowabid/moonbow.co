<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaSynchronizer;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductTypeAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\AttachmentsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping\SkuGroupMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\Mapping\SkuMappingService;

/**
 * @property SkuMappingService $mappingService
 */
class InsertLocalProductService extends \GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\InsertLocalProductService
{
    protected SkuGroupMappingService $skuGroupMappingService;
    protected ?SkuRequestOutput $skuRequestOutput = null;

    public function __construct(
        ProductBaseAdapter $productBaseAdapter,
        SkuMappingService $productsMappingService,
        AttachmentsService $attachmentsService,
        ProductPostMetaSynchronizer $productPostMetaSynchronizer,
        SkuGroupMappingService $skuGroupMappingService
    ) {
        $this->skuGroupMappingService = $skuGroupMappingService;

        parent::__construct($productBaseAdapter, $productsMappingService, $attachmentsService, $productPostMetaSynchronizer);
    }

    /**
     * Sets the SKU request output. This gives us access to the entire v2 object during the insert process.
     *
     * @param SkuRequestOutput $skuRequestOutput
     * @return $this
     */
    public function setSkuRequestOutput(SkuRequestOutput $skuRequestOutput) : InsertLocalProductService
    {
        $this->skuRequestOutput = $skuRequestOutput;

        return $this;
    }

    /**
     * This is overridden because we have to customize the "save remote ID" behaviour based on product type.
     * {@inheritDoc}
     * @param ProductBase $remoteResource
     */
    public function insert(AbstractDataObject $remoteResource) : int
    {
        $localResource = $this->integrationClassName::withoutWrites(fn () => $this->insertLocalResource($remoteResource));

        if (! $localResource || ! is_object($localResource)) {
            throw new InsertLocalResourceException('Failed to retrieve local resource during insertion.');
        }

        $this->saveRemoteProductMappings($localResource, $remoteResource);

        return $this->getLocalResourceId($localResource);
    }

    /**
     * Save the remote ID to the appropriate mapping table based on product type.
     *
     * @param object $localResource
     * @param ProductBase $remoteResource
     * @throws CommerceExceptionContract|MissingProductRemoteIdException
     */
    protected function saveRemoteProductMappings(object $localResource, AbstractDataObject $remoteResource) : void
    {
        $remoteId = $this->getRemoteResourceId($remoteResource);
        $productType = ProductTypeAdapter::getNewInstance()->convertFromSource($remoteResource);

        if ($productType === 'variable') {
            $this->skuGroupMappingService->saveRemoteId($localResource, $remoteId);
        } elseif ($productType === 'variation') {
            $this->mappingService->saveRemoteId($localResource, $remoteId);
        } else {
            // simple
            // at this point $remoteId is for the SKU and we have to separately retrieve the SKU group ID from the SKU request output
            $this->mappingService->saveRemoteId($localResource, $remoteId);

            // sanity check that the SKU in the request output matches the remote ID we're processing
            if ($this->skuRequestOutput && $remoteId === $this->skuRequestOutput->sku->id && ! empty($this->skuRequestOutput->sku->skuGroupId)) {
                $this->skuGroupMappingService->saveRemoteId($localResource, $this->skuRequestOutput->sku->skuGroupId);
            }
        }
    }
}
