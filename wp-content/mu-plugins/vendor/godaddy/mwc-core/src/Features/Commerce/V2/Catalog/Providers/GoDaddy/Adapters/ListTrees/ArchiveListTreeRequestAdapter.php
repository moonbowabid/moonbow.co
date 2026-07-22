<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\ListTrees;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListTreeRequestInputs\ArchiveListTreeInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\AbstractGraphQLGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\ArchiveListTreeOperation;

/**
 * Request adapter for archiving a list tree.
 *
 * @method static static getNewInstance(ArchiveListTreeInput $input)
 * @property ArchiveListTreeInput $input
 */
class ArchiveListTreeRequestAdapter extends AbstractGraphQLGatewayRequestAdapter
{
    public function __construct(ArchiveListTreeInput $input)
    {
        parent::__construct($input);
    }

    /** {@inheritDoc} */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ArchiveListTreeOperation())->setVariables($this->getQueryVariables());
    }

    /** {@inheritDoc} */
    protected function getQueryVariables() : array
    {
        return [
            'id' => $this->input->listTreeId,
        ];
    }

    /**
     * @param ResponseContract $response
     * @return void
     */
    protected function convertResponse(ResponseContract $response) : void
    {
        // no-op
    }
}
