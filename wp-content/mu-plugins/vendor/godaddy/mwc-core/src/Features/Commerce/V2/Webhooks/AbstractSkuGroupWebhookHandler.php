<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\AbstractWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\SkuGroupResponseToProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\SkuGroupService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Services\UpdateLocalProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuGroupMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Webhooks\Traits\CanDetermineStaleProductWebhookTrait;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

abstract class AbstractSkuGroupWebhookHandler extends AbstractWebhookHandler
{
    use CanDetermineStaleProductWebhookTrait;

    protected SkuGroupService $skuGroupService;
    protected SkuGroupMapRepository $skuGroupMapRepository;
    protected UpdateLocalProductService $updateLocalProductService;
    protected SkuGroupResponseToProductBaseAdapter $skuGroupResponseToProductBaseAdapter;

    public function __construct(
        WebhooksRepository $webhooksRepository,
        SkuGroupService $skuGroupService,
        SkuGroupMapRepository $skuGroupMapRepository,
        UpdateLocalProductService $updateLocalProductService,
        SkuGroupResponseToProductBaseAdapter $skuGroupResponseToProductBaseAdapter
    ) {
        $this->skuGroupService = $skuGroupService;
        $this->skuGroupMapRepository = $skuGroupMapRepository;
        $this->updateLocalProductService = $updateLocalProductService;
        $this->skuGroupResponseToProductBaseAdapter = $skuGroupResponseToProductBaseAdapter;

        parent::__construct($webhooksRepository);
    }

    /**
     * Sets the SkuRequestOutput in the UpdateLocalProductService if there is at least one SKU in the SkuGroupRequestOutput.
     *
     * @param SkuGroupRequestOutput $skuGroupRequestOutput
     */
    protected function maybeSetSkuGroupRequestOutput(SkuGroupRequestOutput $skuGroupRequestOutput) : void
    {
        /** @var ?Sku $skuData */
        $skuData = ArrayHelper::get($skuGroupRequestOutput->skus, 0);

        if (! empty($skuData)) {
            $skuRequestData = new SkuRequestOutput([
                'skuGroup' => $skuGroupRequestOutput->skuGroup,
                'sku'      => $skuData,
            ]);

            $this->updateLocalProductService->setSkuRequestOutput($skuRequestData);
        }
    }

    /**
     * JSON path to the SKU group ID property in the payload.
     *
     * @return string
     */
    abstract protected function getPathToSkuGroupIdProperty() : string;

    /** {@inheritDoc} */
    protected function getLocalId(Webhook $webhook) : ?int
    {
        $remoteSkuId = $this->parseSkuGroupFromPayload($webhook);

        $localId = $this->getLocalProductId($remoteSkuId);

        return $localId > 0 ? $localId : null;
    }

    /**
     * Get the local WooCommerce product ID mapped to the given remote SKU Group ID.
     *
     * @param string $remoteSkuGroupId
     * @return int|null Local product ID or null if not found
     */
    protected function getLocalProductId(string $remoteSkuGroupId) : ?int
    {
        return $this->skuGroupMapRepository->getLocalId($remoteSkuGroupId);
    }

    /** {@inheritDoc} */
    public function handle(Webhook $webhook) : void
    {
        if ($this->shouldHandle($webhook)) {
            try {
                $this->handleSkuGroupWebhook($webhook);
            } catch(Exception|CommerceExceptionContract $e) {
                throw new WebhookProcessingException($e->getMessage(), $e);
            }
        }
    }

    /**
     * Handles the webhook by fetching the SKU Group details from the API and updating the local product.
     *
     * @param Webhook $webhook
     * @throws AdapterException
     * @throws CommerceExceptionContract
     * @throws MissingProductRemoteIdException
     * @throws WebhookProcessingException
     */
    protected function handleSkuGroupWebhook(Webhook $webhook) : void
    {
        $remoteSkuGroupId = $this->parseSkuGroupFromPayload($webhook);

        if ($this->isSkuGroupUpdateLocked($remoteSkuGroupId)) {
            return;
        }

        $skuGroupRequestOutput = $this->getSkuGroupFromApi($remoteSkuGroupId);

        $this->maybeUpdateLocalProduct($remoteSkuGroupId, $skuGroupRequestOutput);
    }

    /**
     * Checks whether the given SKU Group is currently being updated from local to remote.
     */
    protected function isSkuGroupUpdateLocked(string $remoteSkuGroupId) : bool
    {
        return $this->skuGroupService->hasUpdateLock($remoteSkuGroupId);
    }

    /**
     * Parses the SKU Group ID from the webhook payload using the given JSON path.
     *
     * @param Webhook $webhook
     * @return string
     * @throws WebhookProcessingException
     */
    protected function parseSkuGroupFromPayload(Webhook $webhook) : string
    {
        $pathToSkuGroupIdProperty = $this->getPathToSkuGroupIdProperty();

        $skuGroupId = TypeHelper::string(ArrayHelper::get(json_decode($webhook->payload, true), $pathToSkuGroupIdProperty), '');
        if (empty($skuGroupId)) {
            throw new WebhookProcessingException('Webhook payload is missing the SKU Group ID at path: '.$pathToSkuGroupIdProperty);
        }

        return $skuGroupId;
    }

    /**
     * Fetch SKU Group details from the API using the remote SKU Group ID.
     *
     * @param string $remoteSkuGroupId
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract|MissingProductRemoteIdException
     */
    protected function getSkuGroupFromApi(string $remoteSkuGroupId) : SkuGroupRequestOutput
    {
        return $this->skuGroupService->get($remoteSkuGroupId);
    }

    /**
     * Updates the local product (if it exists) with the remote data.
     *
     * NOTE: We very intentionally do not check {@see CanDetermineStaleProductWebhookTrait::isStaleProductWebhook()} here
     * because otherwise this would almost always "break" updates for variable products.
     * This is because if a sku.updated webhook comes in first, that triggers a manual save of the parent product in
     * {@see UpdateLocalProductService::maybeSyncParentProduct()}
     * That causes the parent product's "updated at" timestamp to be changed, which effectively invalidates all
     * sku-group.updated webhooks that are already in the queue.
     * @throws AdapterException
     */
    protected function maybeUpdateLocalProduct(string $remoteSkuGroupId, SkuGroupRequestOutput $skuGroupRequestOutput) : void
    {
        $localId = $this->getLocalProductId($remoteSkuGroupId);
        if (! $localId) {
            // creates will happen in the SkuWebhookHandler
            return;
        }

        $wcProduct = ProductsRepository::get($localId);

        if (! $wcProduct) {
            return;
        }

        if ($wcProduct->get_type() === 'simple') {
            // Simple products need SKU data to be fully represented because in Woo they are a combination of Sku Group + one Sku
            $productBase = $this->skuGroupResponseToProductBaseAdapter->convertWithFirstSkuData($skuGroupRequestOutput);

            $this->maybeSetSkuGroupRequestOutput($skuGroupRequestOutput);
        } else {
            $productBase = $this->skuGroupResponseToProductBaseAdapter->convert($skuGroupRequestOutput);
        }

        $this->updateLocalProductService->setSkuGroupRequestOutput($skuGroupRequestOutput);
        $this->updateLocalProductService->update($productBase, $localId);
    }
}
