<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Services;

use DateTimeImmutable;
use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ReadLevelOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\CreateOrUpdateLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ListLevelsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Adapters\InventoryCountToLevelAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Builders\CreateInventoryAdjustmentInputBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryCount;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CreateInventoryAdjustmentInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ListInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\ReadInventoryCountInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\ReadInventoryCountOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\Enums\InventoryCountType;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\InventoryProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Repositories\SkuMapRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Inventory counts service for V2.
 * This is the equivalent of the v1 levels service. {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsService}.
 *
 * The v2 schema does not actually have a `Levels` object; instead it uses `InventoryCount` objects. However, we have to
 * re-map all responses to use a `Level` object to maintain compatibility with the rest of the generic MWC codebase.
 */
class InventoryCountsService implements LevelsServiceContract
{
    protected CommerceContextContract $commerceContext;
    protected InventoryProvider $provider;
    protected SkuMapRepository $skuMapRepository;
    protected LocationMappingServiceContract $locationMappingService;

    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProvider $provider,
        SkuMapRepository $skuMapRepository,
        LocationMappingService $locationMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->provider = $provider;
        $this->skuMapRepository = $skuMapRepository;
        $this->locationMappingService = $locationMappingService;
    }

    /**
     * Unlike the v1 API, the v2 API does not support direct setting of inventory levels. Instead, we have to submit
     * an "inventory adjustment", which is a delta to apply to the current level. e.g. "increase by 5" or "decrease by 3".
     * This means we have to take the desired on-hand quantity from the core Product object, compare it to the current
     * inventory counts from the API, and calculate the delta needed to reach the desired level.
     *
     * {@inheritDoc}
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        $product = $operation->getProduct();

        // Get current inventory counts from API
        $currentInventoryCounts = $this->getCurrentInventoryCounts($product);

        // Build the adjustment input
        $adjustmentInput = $this->getAdjustmentInput($product, $currentInventoryCounts);

        // If no adjustment needed, return current level
        if (! $adjustmentInput) {
            return new CreateOrUpdateLevelResponse($this->convertInventoryCountsToLevel($currentInventoryCounts));
        }

        // Create adjustment
        $this->provider->adjustments()->create($adjustmentInput);

        // Return updated level from API
        // We do another API request here because the adjustment response only includes the amount for that one type (`AVAILABLE`).
        // To get the full picture we need to re-fetch all inventory counts for the SKU/location
        // and convert them to a Level object.
        $readOperation = new ReadLevelOperation($product);

        return new CreateOrUpdateLevelResponse($this->readLevel($readOperation)->getLevel());
    }

    /**
     * Gets adjustment input for a product based on desired on-hand quantity and current inventory counts.
     *
     * @param Product $product
     * @param InventoryCount[] $currentInventoryCounts
     * @return CreateInventoryAdjustmentInput|null Returns null if no adjustment is needed
     * @throws CommerceException
     * @throws MissingLevelRemoteIdException
     * @throws MissingProductLocalIdException
     */
    protected function getAdjustmentInput(Product $product, array $currentInventoryCounts) : ?CreateInventoryAdjustmentInput
    {
        $adjustmentInputBuilder = CreateInventoryAdjustmentInputBuilder::getNewInstance(
            $this->commerceContext->getStoreId(),
            $this->getSkuRemoteId($product),
            $this->getLocationId()
        );

        return $adjustmentInputBuilder->build($product, $currentInventoryCounts);
    }

    /**
     * This isn't needed in v2, since there are no remote IDs for level objects. So we'll never run into a situation
     * where a "create" is attempted, but a level already exists upstream.
     *
     * {@inheritDoc}
     */
    public function createOrUpdateLevelWithRepair(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        return $this->createOrUpdateLevel($operation);
    }

    /** {@inheritDoc} */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        $inventoryCounts = $this->getCurrentInventoryCounts($operation->getProduct());

        return new ReadLevelResponse($this->convertInventoryCountsToLevel($inventoryCounts));
    }

    /**
     * This isn't needed in v2, since there are no remote IDs for level objects.
     * {@inheritDoc}
     */
    public function readLevelWithRepair(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        return $this->readLevel($operation);
    }

    /**
     * Extracts inventory counts from the output, or creates a dummy one with zero quantity if none exist.
     *
     * @param ReadInventoryCountInput $input
     * @param ReadInventoryCountOutput $output
     * @return InventoryCount[]
     */
    protected function getInventoryCountsFromOutput(ReadInventoryCountInput $input, ReadInventoryCountOutput $output) : array
    {
        if (! empty($output->inventoryCounts)) {
            return $output->inventoryCounts;
        }

        /*
         * A null `inventoryCounts` indicates that no inventory count exists for the given SKU/location. In this case,
         * the expectation from the response contract is that we return a Level with zero quantity.
         * To facilitate that we'll need to create a dummy InventoryCount DTO.
         * @link https://github.com/gdcorp-partners/mwc-core/pull/7734/files#r2381565426
         */
        $now = new DateTimeImmutable();

        return [
            new InventoryCount([
                'id'       => '',
                'quantity' => 0,
                'onHand'   => 0,
                'type'     => $input->type ?: InventoryCountType::Available,
                'sku'      => new Sku([
                    'id'             => $input->skuId,
                    'backorderLimit' => null,
                ]),
                'locationId' => $input->locationId,
                'createdAt'  => $now->format('Y-m-d H:i:s'),
                'updatedAt'  => $now->format('Y-m-d H:i:s'),
            ]),
        ];
    }

    /**
     * @param Product $product
     * @return ReadInventoryCountInput
     * @throws CommerceException
     * @throws MissingLevelRemoteIdException
     * @throws MissingProductLocalIdException
     */
    protected function getReadLevelInput(Product $product) : ReadInventoryCountInput
    {
        return new ReadInventoryCountInput([
            'storeId'    => $this->commerceContext->getStoreId(),
            'skuId'      => $this->getSkuRemoteId($product),
            'locationId' => $this->getLocationId(),
            'type'       => null, // we want to retrieve all types
        ]);
    }

    /**
     * Converts an array of InventoryCount objects to a single Level object.
     *
     * @param InventoryCount[] $inventoryCounts
     * @return Level
     * @throws Exception
     */
    protected function convertInventoryCountsToLevel(array $inventoryCounts) : Level
    {
        return InventoryCountToLevelAdapter::getNewInstance()->convert($inventoryCounts);
    }

    /** {@inheritDoc} */
    public function listLevelsByRemoteProductId(ListLevelsByRemoteIdOperationContract $operation) : ListLevelsResponseContract
    {
        $input = $this->getListInventoryCountInput($operation);

        $output = $this->provider->inventoryCounts()->list($input);

        // convert each group of counts to a single Level object, ensuring we return a Level for each requested SKU ID
        $levels = $this->convertGroupedInventoryCountsToLevels(
            $output->groupedInventoryCounts,
            $operation->getIds() ?? [],
            $input
        );

        return new ListLevelsResponse($levels);
    }

    /**
     * Creates the input for listing inventory counts.
     *
     * @param ListLevelsByRemoteIdOperationContract $operation
     * @return ListInventoryCountInput
     * @throws CommerceException
     */
    protected function getListInventoryCountInput(ListLevelsByRemoteIdOperationContract $operation) : ListInventoryCountInput
    {
        $skuIds = $operation->getIds();
        if (empty($skuIds)) {
            throw new CommerceException('SKU IDs are required to list inventory levels');
        }

        $locationId = $this->locationMappingService->getRemoteId();
        if (! $locationId) {
            throw new CommerceException('Missing location ID');
        }

        return new ListInventoryCountInput([
            'storeId'    => $this->commerceContext->getStoreId(),
            'skuIds'     => $skuIds,
            'locationId' => $locationId,
            'type'       => null, // we want to retrieve all types
        ]);
    }

    /**
     * Converts grouped inventory counts to Level objects.
     *
     * @param array<string, InventoryCount[]> $groupedCounts
     * @param string[] $requestedSkuIds
     * @param ListInventoryCountInput $input
     * @return Level[]
     * @throws Exception
     */
    protected function convertGroupedInventoryCountsToLevels(array $groupedCounts, array $requestedSkuIds, ListInventoryCountInput $input) : array
    {
        $levels = [];

        foreach ($requestedSkuIds as $skuId) {
            $inventoryCounts = $groupedCounts[$skuId] ?? [];

            // If no inventory counts exist for this SKU, create dummy ones with zero quantity
            if (empty($inventoryCounts)) {
                $inventoryCounts = $this->createDummyInventoryCountsForSkuAndLocation(
                    $skuId,
                    $input->locationId,
                    $input->type ?? InventoryCountType::Available
                );
            }

            $levels[] = $this->convertInventoryCountsToLevel($inventoryCounts);
        }

        return $levels;
    }

    /**
     * Creates a dummy inventory count with zero quantities for the given SKU, location, and type.
     *
     * A null `inventoryCounts` in {@see ReadInventoryCountOutput} indicates that no inventory count exists in the
     * remote API for the given SKU/location. In this case, the expectation from the response contract is that we
     * return a Level with zero quantity. To facilitate that we'll need to create a dummy InventoryCount DTO.
     * @link https://github.com/gdcorp-partners/mwc-core/pull/7734/files#r2381565426
     *
     * @param string $skuId
     * @param string|null $locationId
     * @param string $type
     * @return InventoryCount[]
     */
    protected function createDummyInventoryCountsForSkuAndLocation(string $skuId, ?string $locationId, string $type) : array
    {
        $now = new DateTimeImmutable();

        if (! $locationId) {
            $locationId = $this->locationMappingService->getRemoteId() ?: '';
        }

        return [
            new InventoryCount([
                'id'       => '',
                'quantity' => 0,
                'onHand'   => 0,
                'type'     => $type,
                'sku'      => new Sku([
                    'id'             => $skuId,
                    'backorderLimit' => null,
                ]),
                'locationId' => $locationId,
                'createdAt'  => $now->format('Y-m-d H:i:s'),
                'updatedAt'  => $now->format('Y-m-d H:i:s'),
            ]),
        ];
    }

    /** {@inheritDoc} */
    public function mapLevelToProduct(array $levels, Product $product) : void
    {
        // no-op, as v2 levels have no remote IDs to map
    }

    /**
     * Gets the SKU remote ID for a product.
     *
     * @param Product $product
     * @return string
     * @throws MissingProductLocalIdException
     * @throws MissingLevelRemoteIdException
     */
    protected function getSkuRemoteId(Product $product) : string
    {
        $productId = $product->getId();
        if (! $productId) {
            throw new MissingProductLocalIdException('Product must have a local ID');
        }

        $skuId = $this->skuMapRepository->getRemoteId($productId);
        if (! $skuId) {
            throw new MissingLevelRemoteIdException('Local product does not have a mapped SKU id');
        }

        return $skuId;
    }

    /**
     * Gets current inventory counts for a product.
     *
     * @param Product $product
     * @return InventoryCount[]
     * @throws CommerceExceptionContract
     * @throws MissingProductLocalIdException
     * @throws MissingLevelRemoteIdException
     */
    protected function getCurrentInventoryCounts(Product $product) : array
    {
        $input = $this->getReadLevelInput($product);
        $output = $this->provider->inventoryCounts()->read($input);

        return $this->getInventoryCountsFromOutput($input, $output);
    }

    /**
     * Gets the location ID from the mapping service.
     *
     * @return string
     * @throws CommerceException
     */
    protected function getLocationId() : string
    {
        $locationId = $this->locationMappingService->getRemoteId();
        if (! $locationId) {
            throw new CommerceException('No primary location found');
        }

        return $locationId;
    }
}
