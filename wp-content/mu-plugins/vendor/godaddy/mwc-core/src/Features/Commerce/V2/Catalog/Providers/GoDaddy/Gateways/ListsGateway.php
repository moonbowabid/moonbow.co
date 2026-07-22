<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\Traits\CanDoAdaptedRequestWithExceptionHandlingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts\ListsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\ArchiveListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\CreateListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\QueryListsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\UpdateListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists\ArchiveListRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists\CreateListRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists\QueryListsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Lists\UpdateListRequestAdapter;

/**
 * Gateway for handling list operations with the V2 API.
 */
class ListsGateway extends AbstractGateway implements ListsGatewayContract
{
    use CanGetNewInstanceTrait;
    use CanDoAdaptedRequestWithExceptionHandlingTrait;

    /**
     * Creates a list.
     *
     * @param CreateListInput $input
     * @return ListObject
     * @throws CommerceExceptionContract
     */
    public function create(CreateListInput $input) : ListObject
    {
        /** @var ListObject $result */
        $result = $this->doAdaptedRequest(CreateListRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a list.
     *
     * @param UpdateListInput $input
     * @return ListObject
     * @throws CommerceExceptionContract
     */
    public function update(UpdateListInput $input) : ListObject
    {
        /** @var ListObject $result */
        $result = $this->doAdaptedRequest(UpdateListRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Archives a list.
     *
     * @param ArchiveListInput $input
     * @return ListObject
     * @throws CommerceExceptionContract
     */
    public function archive(ArchiveListInput $input) : ListObject
    {
        /** @var ListObject $result */
        $result = $this->doAdaptedRequest(ArchiveListRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Queries lists based on the provided input.
     *
     * @param QueryListsInput $input
     * @return ListCategoriesResponseContract
     * @throws CommerceExceptionContract
     */
    public function query(QueryListsInput $input) : ListCategoriesResponseContract
    {
        /** @var ListCategoriesResponseContract $result */
        $result = $this->doAdaptedRequest(QueryListsRequestAdapter::getNewInstance($input));

        return $result;
    }
}
