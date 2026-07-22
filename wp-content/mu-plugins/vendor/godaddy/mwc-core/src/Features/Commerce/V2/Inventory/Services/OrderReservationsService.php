<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Inventory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\AbstractOrderReservationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Traits\CanDetermineLineItemProductForReservationTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Adapters\InventoryAdjustmentToReservationAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CommitInventoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\InventoryProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Product;

/**
 * Order reservations service for V2.
 * This service commits inventory using the v2 GraphQL commitInventory mutation.
 */
class OrderReservationsService extends AbstractOrderReservationsService
{
    use CanDetermineLineItemProductForReservationTrait;

    protected CommerceContextContract $commerceContext;
    protected InventoryProvider $inventoryProvider;
    protected SkuMapRepository $skuMapRepository;
    protected LocationMappingService $locationMappingService;

    /**
     * @param CommerceContextContract $commerceContext
     * @param InventoryProvider $inventoryProvider
     * @param SkuMapRepository $skuMapRepository
     * @param LocationMappingService $locationMappingService
     * @param ProductInventoryCachingServiceContract $inventoryCachingService
     * @param WooOrderCartIdProvider $cartIdProvider
     */
    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProvider $inventoryProvider,
        SkuMapRepository $skuMapRepository,
        LocationMappingService $locationMappingService,
        ProductInventoryCachingServiceContract $inventoryCachingService,
        WooOrderCartIdProvider $cartIdProvider
    ) {
        $this->commerceContext = $commerceContext;
        $this->inventoryProvider = $inventoryProvider;
        $this->skuMapRepository = $skuMapRepository;
        $this->locationMappingService = $locationMappingService;

        parent::__construct($inventoryCachingService, $cartIdProvider);
    }

    /**
     * Despite the naming here, this only ever does a "create" operation. Naming is just to abide by the contract signature.
     *
     * {@inheritDoc}
     */
    protected function createOrUpdateReservationInRemoteService(WC_Product $product, LineItem $lineItem, Order $order) : array
    {
        $inventoryAdjustments = $this->commitInventoryInRemoteService($product, $lineItem, $order);

        // Convert InventoryAdjustments to Reservations for v1 compatibility
        return $this->convertAdjustmentsToReservations($inventoryAdjustments);
    }

    /**
     * Builds the input object to read a remote adjustment record by its ID.
     */
    protected function getReadInventoryAdjustmentInput(string $remoteAdjustmentId) : ReadInventoryAdjustmentInput
    {
        return new ReadInventoryAdjustmentInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'id'      => $remoteAdjustmentId,
        ]);
    }

    /**
     * Commits inventory in the remote service.
     * @return InventoryAdjustment[]
     * @throws MissingProductRemoteIdException|CommerceExceptionContract|Exception
     */
    protected function commitInventoryInRemoteService(WC_Product $product, LineItem $lineItem, Order $order) : array
    {
        $input = $this->buildCommitInventoryInput($product, $lineItem, $order);
        $output = $this->inventoryProvider->adjustments()->commitForOrder($input);

        return $output->inventoryAdjustments;
    }

    /**
     * Builds the commit inventory input for a product and line item.
     *
     * @throws MissingProductRemoteIdException|Exception
     */
    protected function buildCommitInventoryInput(WC_Product $wcProduct, LineItem $lineItem, Order $order) : CommitInventoryInput
    {
        $productForReservation = $this->getProductForReservation($lineItem);
        if (! $productForReservation || ! $productForReservation->getId()) {
            throw new MissingProductRemoteIdException('Product must have a local ID');
        }

        $skuId = $this->skuMapRepository->getRemoteId($productForReservation->getId());
        if (! $skuId) {
            throw new MissingProductRemoteIdException('Local product does not have a mapped SKU ID');
        }

        $locationId = $this->locationMappingService->getRemoteId();
        if (! $locationId) {
            throw new MissingProductRemoteIdException('No primary location found');
        }

        return new CommitInventoryInput([
            'storeId'         => $this->commerceContext->getStoreId(),
            'skuId'           => $skuId,
            'locationId'      => $locationId,
            'quantity'        => TypeHelper::int($lineItem->getQuantity(), 0),
            'allowBackorders' => $wcProduct->backorders_allowed(),
            'references'      => [
                new Reference([
                    'origin' => 'CART',
                    'value'  => TypeHelper::string($order->getCartId(), ''),
                ]),
            ],
        ]);
    }

    /**
     * Converts an array of InventoryAdjustment objects to Reservations for v1 compatibility.
     *
     * @param InventoryAdjustment[] $inventoryAdjustments
     * @return Reservation[]
     */
    protected function convertAdjustmentsToReservations(array $inventoryAdjustments) : array
    {
        $reservations = [];
        foreach ($inventoryAdjustments as $inventoryAdjustment) {
            try {
                $reservations[] = $this->convertAdjustmentToReservation($inventoryAdjustment);
            } catch(AdapterException $e) {
                // do nothing
            }
        }

        return $reservations;
    }

    /**
     * Converts an InventoryAdjustment to a Reservation for event compatibility.
     * @throws AdapterException
     */
    protected function convertAdjustmentToReservation(InventoryAdjustment $adjustment) : Reservation
    {
        return InventoryAdjustmentToReservationAdapter::getNewInstance()->convert($adjustment);
    }
}
