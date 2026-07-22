<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\DataObjects\Collection;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Adapters\ReferencesAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\Reference;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\InventoryAdjustment;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestInputs\CommitInventoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\RequestOutputs\CommitInventoryOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\DataObjects\Sku;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Inventory\Providers\GoDaddy\Http\GraphQL\Mutations\CommitInventoryOperation;

/**
 * Adapts a commit inventory request for the GoDaddy GraphQL API.
 *
 * @method static static getNewInstance(CommitInventoryInput $input)
 * @property CommitInventoryInput $input
 */
class CommitInventoryRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    /**
     * CommitInventoryRequestAdapter constructor.
     *
     * @param CommitInventoryInput $input
     */
    public function __construct(CommitInventoryInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new CommitInventoryOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'input' => [
                'skuId'           => $this->input->skuId,
                'locationId'      => $this->input->locationId,
                'quantity'        => $this->input->quantity,
                'allowBackorders' => $this->input->allowBackorders,
                'references'      => (new Collection($this->input->references))->toArray(),
            ],
        ];
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response) : CommitInventoryOutput
    {
        $responseBody = TypeHelper::arrayOfStringsAsKeys($response->getBody());
        $commitmentData = TypeHelper::array(ArrayHelper::get($responseBody, 'data.commitInventory', []), []);

        return new CommitInventoryOutput([
            'inventoryAdjustments' => $this->parseAdjustments($commitmentData),
        ]);
    }

    /**
     * Parses the provided array of adjustment data, and converts each item into a {@see InventoryAdjustment} DTO.
     *
     * @param array<mixed> $adjustmentData
     * @return InventoryAdjustment[]
     */
    protected function parseAdjustments(array $adjustmentData) : array
    {
        $adjustments = [];

        foreach ($adjustmentData as $adjustment) {
            $adjustments[] = $this->convertInventoryAdjustmentFromGraphQLData(TypeHelper::arrayOfStringsAsKeys($adjustment));
        }

        return $adjustments;
    }

    /**
     * Converts GraphQL data to an InventoryAdjustment object.
     *
     * @param array<string, mixed> $commitmentData
     * @return InventoryAdjustment
     */
    protected function convertInventoryAdjustmentFromGraphQLData(array $commitmentData) : InventoryAdjustment
    {
        $backorderLimit = ArrayHelper::get($commitmentData, 'sku.backorderLimit');

        return new InventoryAdjustment([
            'id'         => TypeHelper::string(ArrayHelper::get($commitmentData, 'id'), ''),
            'delta'      => TypeHelper::int(ArrayHelper::get($commitmentData, 'delta'), 0),
            'type'       => TypeHelper::string(ArrayHelper::get($commitmentData, 'type'), ''),
            'occurredAt' => TypeHelper::string(ArrayHelper::get($commitmentData, 'occurredAt'), ''),
            'sku'        => new Sku([
                'id'             => TypeHelper::string(ArrayHelper::get($commitmentData, 'sku.id'), ''),
                'backorderLimit' => is_numeric($backorderLimit) ? TypeHelper::int($backorderLimit, 0) : null,
            ]),
            'locationId' => TypeHelper::string(ArrayHelper::get($commitmentData, 'location.id'), ''),
            'references' => $this->extractInventoryAdjustmentReferences($commitmentData),
        ]);
    }

    /**
     * Parses references out of the response.
     *
     * @param array<string, mixed> $commitmentData
     * @return array<Reference>
     */
    protected function extractInventoryAdjustmentReferences(array $commitmentData) : array
    {
        return ReferencesAdapter::getNewInstance($commitmentData)->convertFromSource();
    }
}
