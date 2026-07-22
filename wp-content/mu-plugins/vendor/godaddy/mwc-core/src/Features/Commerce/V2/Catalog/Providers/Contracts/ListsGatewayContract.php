<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\ArchiveListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\CreateListInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\QueryListsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListRequestInputs\UpdateListInput;

/**
 * Contract for list gateways.
 */
interface ListsGatewayContract
{
    /**
     * Creates a list.
     *
     * @param CreateListInput $input
     * @return ListObject
     * @throws CommerceExceptionContract
     */
    public function create(CreateListInput $input) : ListObject;

    /**
     * Updates a list.
     *
     * @param UpdateListInput $input
     * @return ListObject
     * @throws CommerceExceptionContract
     */
    public function update(UpdateListInput $input) : ListObject;

    /**
     * Archives a list.
     *
     * @param ArchiveListInput $input
     * @return ListObject
     * @throws CommerceExceptionContract
     */
    public function archive(ArchiveListInput $input) : ListObject;

    /**
     * Queries lists based on the provided input.
     *
     * @param QueryListsInput $input
     * @return ListCategoriesResponseContract
     * @throws CommerceExceptionContract
     */
    public function query(QueryListsInput $input) : ListCategoriesResponseContract;
}
