<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\SkuGroupsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\ArchiveSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\CreateSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\GetSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuGroupInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateSkuGroupRelationshipsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ProductRequestOutputs\SkuGroupRequestOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\ArchiveSkuGroupRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\CreateSkuGroupRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\GetSkuGroupRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\UpdateSkuGroupRelationshipsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\SkuGroups\UpdateSkuGroupRequestAdapter;

/**
 * Gateway for handling SKU group operations with the V2 API.
 */
class SkuGroupsGateway extends AbstractGateway implements SkuGroupsGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * Creates a SKU group.
     *
     * @param CreateSkuGroupInput $input
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract
     */
    public function create(CreateSkuGroupInput $input) : SkuGroupRequestOutput
    {
        /** @var SkuGroupRequestOutput $result */
        $result = $this->doAdaptedRequest(CreateSkuGroupRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a SKU group.
     *
     * @param UpdateSkuGroupInput $input
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract
     */
    public function update(UpdateSkuGroupInput $input) : SkuGroupRequestOutput
    {
        /** @var SkuGroupRequestOutput $result */
        $result = $this->doAdaptedRequest(UpdateSkuGroupRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates SKU group relationships.
     *
     * @param UpdateSkuGroupRelationshipsInput $input
     * @throws CommerceExceptionContract
     */
    public function updateRelationships(UpdateSkuGroupRelationshipsInput $input) : void
    {
        $this->doAdaptedRequest(UpdateSkuGroupRelationshipsRequestAdapter::getNewInstance($input));
    }

    /**
     * Archives a SKU group, optionally cascading the archive to every SKU in the group.
     *
     * @param ArchiveSkuGroupInput $input
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract
     */
    public function archive(ArchiveSkuGroupInput $input) : SkuGroupRequestOutput
    {
        /** @var SkuGroupRequestOutput $result */
        $result = $this->doAdaptedRequest(ArchiveSkuGroupRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Gets a SKU group.
     *
     * @param GetSkuGroupInput $input
     * @return SkuGroupRequestOutput
     * @throws CommerceExceptionContract
     */
    public function get(GetSkuGroupInput $input) : SkuGroupRequestOutput
    {
        /** @var SkuGroupRequestOutput $result */
        $result = $this->doAdaptedRequest(GetSkuGroupRequestAdapter::getNewInstance($input));

        return $result;
    }
}
