<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\AbstractWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\Traits\CanInsertLocalProductsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\SkuGroupResponseToProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\SkuResponseToProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\InsertLocalProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\UpdateLocalProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuGroupMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks\Traits\CanDetermineStaleProductWebhookTrait;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Abstract class for handling any webhooks that contain a SKU ID. Examples include:
 * commerce.catalog.sku.created
 * commerce.catalog.sku.updated
 * commerce.catalog.sku-price.updated
 */
abstract class AbstractSkuWebhookHandler extends AbstractWebhookHandler
{
    use CanDetermineStaleProductWebhookTrait;

    protected SkuService $skuService;
    protected SkuMapRepository $skuMapRepository;
    protected SkuGroupMapRepository $skuGroupMapRepository;
    protected InsertLocalProductService $insertLocalProductService;
    protected UpdateLocalProductService $updateLocalProductService;
    protected SkuResponseToProductBaseAdapter $skuResponseToProductBaseAdapter;
    protected SkuGroupResponseToProductBaseAdapter $skuGroupResponseToProductBaseAdapter;

    public function __construct(
        SkuService $skuService,
        SkuMapRepository $skuMapRepository,
        SkuGroupMapRepository $skuGroupMapRepository,
        WebhooksRepository $webhooksRepository,
        InsertLocalProductService $insertLocalProductService,
        UpdateLocalProductService $updateLocalProductService,
        SkuResponseToProductBaseAdapter $skuResponseToProductBaseAdapter,
        SkuGroupResponseToProductBaseAdapter $skuGroupResponseToProductBaseAdapter
    ) {
        $this->skuService = $skuService;
        $this->skuMapRepository = $skuMapRepository;
        $this->skuGroupMapRepository = $skuGroupMapRepository;
        $this->insertLocalProductService = $insertLocalProductService;
        $this->updateLocalProductService = $updateLocalProductService;
        $this->skuResponseToProductBaseAdapter = $skuResponseToProductBaseAdapter;
        $this->skuGroupResponseToProductBaseAdapter = $skuGroupResponseToProductBaseAdapter;

        parent::__construct($webhooksRepository);
    }

    /**
     * JSON path to the SKU ID property in the payload.
     *
     * @return string
     */
    abstract protected function getPathToSkuIdProperty() : string;

    /** {@inheritDoc} */
    protected function getLocalId(Webhook $webhook) : ?int
    {
        $remoteSkuId = $this->parseSkuFromPayload($webhook);

        $localId = $this->getLocalProductId($remoteSkuId);

        return $localId > 0 ? $localId : null;
    }

    /**
     * Get the local WooCommerce product ID mapped to the given remote SKU ID.
     *
     * @param string $remoteSkuId
     * @return int|null Local SKU ID or null if not found
     */
    protected function getLocalProductId(string $remoteSkuId) : ?int
    {
        return $this->skuMapRepository->getLocalId($remoteSkuId);
    }

    /**
     * Fetch SKU details from the API using the remote SKU ID.
     *
     * @param string $remoteSkuId
     * @return SkuRequestOutput
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     */
    protected function getSkuFromApi(string $remoteSkuId) : SkuRequestOutput
    {
        return $this->skuService->get($remoteSkuId);
    }

    /** {@inheritDoc} */
    public function handle(Webhook $webhook) : void
    {
        if ($this->shouldHandle($webhook)) {
            try {
                $this->handleSkuWebhook($webhook);
            } catch(Exception|CommerceExceptionContract $e) {
                throw new WebhookProcessingException($e->getMessage(), $e);
            }
        }
    }

    /**
     * Handles the webhook by fetching the SKU details from the API and updating or inserting the local product.
     *
     * @param Webhook $webhook
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     * @throws WebhookProcessingException
     */
    protected function handleSkuWebhook(Webhook $webhook) : void
    {
        $remoteSkuId = $this->parseSkuFromPayload($webhook);

        if ($this->isSkuUpdateLocked($remoteSkuId)) {
            return;
        }

        $skuRequestOutput = $this->getSkuFromApi($remoteSkuId);

        $this->updateOrCreateLocalProduct($webhook, $remoteSkuId, $skuRequestOutput);
    }

    /**
     * Checks whether the given SKU is currently being updated from local to remote.
     */
    protected function isSkuUpdateLocked(string $remoteSkuId) : bool
    {
        return $this->skuService->hasUpdateLock($remoteSkuId);
    }

    /**
     * Updates an existing local product or creates a new one based on the remote SKU data.
     *
     * @param Webhook $webhook
     * @param string $remoteSkuId
     * @param SkuRequestOutput $skuRequestOutput
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     * @throws WebhookProcessingException
     */
    protected function updateOrCreateLocalProduct(Webhook $webhook, string $remoteSkuId, SkuRequestOutput $skuRequestOutput) : void
    {
        // convert to ProductBase
        $productBase = $this->skuResponseToProductBaseAdapter->convert($skuRequestOutput);

        if ($localId = $this->getLocalProductId($remoteSkuId)) {
            $this->maybeUpdateLocalProduct($webhook, $productBase, $localId, $skuRequestOutput);
        } elseif (! $this->isArchivedSku($skuRequestOutput)) {
            /*
             * Only insert product if it is not archived.
             * When a product is permanently deleted, the local mapping is removed and the platform archives
             * the SKU. Without this check, the incoming webhook would recreate the product as a draft.
             */
            $this->insertLocalProduct($webhook, $productBase, $skuRequestOutput);
        }
    }

    /**
     * Determines whether the SKU is archived and should not be created locally.
     */
    protected function isArchivedSku(SkuRequestOutput $skuRequestOutput) : bool
    {
        return ($skuRequestOutput->sku->status ?? null) === 'ARCHIVED'
            || ($skuRequestOutput->skuGroup->status ?? null) === 'ARCHIVED';
    }

    /**
     * Parses the SKU ID from the webhook payload using the given JSON path.
     *
     * @param Webhook $webhook
     * @return string
     * @throws WebhookProcessingException
     */
    protected function parseSkuFromPayload(Webhook $webhook) : string
    {
        $pathToSkuIdProperty = $this->getPathToSkuIdProperty();

        $skuId = TypeHelper::string(ArrayHelper::get(json_decode($webhook->payload, true), $pathToSkuIdProperty), '');
        if (empty($skuId)) {
            throw new WebhookProcessingException('Webhook payload is missing the SKU ID at path: '.$pathToSkuIdProperty);
        }

        return $skuId;
    }

    /**
     * Creates a local product using the information from the given {@see ProductBase} data object.
     *
     * Not using {@see CanInsertLocalProductsTrait} here because {@see CanInsertLocalProductsTrait::maybeCreateVariantProduct}
     * won't work in v2. We also need to attach the SkuRequestOutput to the insert service so it can save the SKU Group ID mapping.
     *
     * @throws WebhookProcessingException
     */
    protected function insertLocalProduct(Webhook $webhook, ProductBase $productBase, SkuRequestOutput $skuRequestOutput) : void
    {
        try {
            $this->maybeInsertLocalParentProduct($productBase, $skuRequestOutput);
            $this->insertLocalProductService->setSkuRequestOutput($skuRequestOutput)->insert($productBase);
        } catch (Exception|CommerceExceptionContract $e) {
            throw new WebhookProcessingException(sprintf(
                'Failed to insert remote product ID %s due to exception %s; Message: %s',
                $webhook->remoteResourceId,
                get_class($e),
                $e->getMessage()
            ), $e);
        }
    }

    /**
     * Maybe creates a local parent (variable) product from the given child.
     * This is a pre-flight check to ensure the local parent exists before we attempt to create the child in {@see static::insertLocalProduct()}.
     *
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws InsertLocalResourceException
     */
    protected function maybeInsertLocalParentProduct(ProductBase $childProduct, SkuRequestOutput $skuRequestOutput) : void
    {
        // bail if there's no parent or no child ID
        if (! $childProduct->parentId || ! $childProduct->productId) {
            return;
        }

        // bail if the parent already exists locally
        if ($this->skuGroupMapRepository->getLocalId($childProduct->parentId)) {
            return;
        }

        // convert sku group to product base
        $skuGroupRequestOutput = new SkuGroupRequestOutput([
            'skuGroup' => $skuRequestOutput->skuGroup,
        ]);

        $parentProductBase = $this->skuGroupResponseToProductBaseAdapter->convert($skuGroupRequestOutput);

        // we need to attach at least one variant sku ID here so that `ProductTypeAdapter` picks this up as a variable product
        $parentProductBase->variants = [$childProduct->productId];

        $this->insertLocalProductService->setSkuRequestOutput($skuRequestOutput)->insert($parentProductBase);
    }

    /**
     * Updates a local WooCommerce product using the information from the given {@see ProductBase} data object.
     * We only want to update if the remote resource was updated more recently than the local product.
     */
    protected function maybeUpdateLocalProduct(Webhook $webhook, ProductBase $productBase, int $localId, SkuRequestOutput $skuRequestOutput) : void
    {
        if ($this->isStaleProductWebhook($webhook, $localId)) {
            return;
        }

        $this->updateLocalProductService->setSkuRequestOutput($skuRequestOutput);
        $this->updateLocalProductService->update($productBase, $localId);
    }
}
