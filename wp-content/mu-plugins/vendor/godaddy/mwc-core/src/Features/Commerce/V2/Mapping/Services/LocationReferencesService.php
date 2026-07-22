<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferences;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferencesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\DataObjects\LocationReferencesOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Services\Contracts\ReferencesServiceContract;

/**
 * Service for retrieving Location references from the v2 Commerce API.
 */
class LocationReferencesService extends AbstractReferencesService implements ReferencesServiceContract
{
    /**
     * Retrieves Location references for the given reference values.
     * {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Queries\LocationReferencesOperation} for the GraphQL operation used.
     *
     * @param array<int, string> $referenceValues
     * @return LocationReferences[]
     * @throws CommerceExceptionContract
     * @throws GatewayRequestException
     */
    public function getReferencesByReferenceValues(array $referenceValues) : array
    {
        if (empty($referenceValues)) {
            return [];
        }

        /** @var LocationReferencesOutput $referencesOutput */
        $referencesOutput = $this->getReferences(
            $this->getLocationReferencesInput($referenceValues),
            LocationReferencesOutput::class
        );

        return $referencesOutput->locationReferences;
    }

    /**
     * Creates input for Location references GraphQL operation.
     *
     * @param array<int, string> $referenceValues
     * @return LocationReferencesInput
     */
    protected function getLocationReferencesInput(array $referenceValues) : LocationReferencesInput
    {
        // Create input for the GraphQL operation
        return new LocationReferencesInput([
            'storeId'         => $this->getStoreId(),
            'referenceValues' => $referenceValues,
            'cursor'          => null,
            'perPage'         => count($referenceValues),
        ]);
    }
}
