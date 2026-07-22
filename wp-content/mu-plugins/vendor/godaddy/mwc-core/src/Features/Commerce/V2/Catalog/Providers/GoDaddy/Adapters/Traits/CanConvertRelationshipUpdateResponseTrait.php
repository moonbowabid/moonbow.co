<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;

/**
 * Trait for extracting data from relationship update responses.
 *
 * This trait provides shared logic for extracting data from relationship update mutation responses
 * (like media additions/removals) that can be used by both SKU and SKU Group adapters.
 */
trait CanConvertRelationshipUpdateResponseTrait
{
    /**
     * Extracts updated data from the response, preferring the most complete data available.
     *
     * @param array<string, mixed> $responseBody
     * @param array<string> $possibleMutations List of mutation names to check for in the response
     * @return mixed
     * @throws CommerceException
     */
    protected function extractUpdatedDataFromRelationshipResponse(array $responseBody, array $possibleMutations)
    {
        $dataSection = ArrayHelper::get($responseBody, 'data', []);

        // Try to find data from any of the provided mutations
        foreach ($possibleMutations as $mutationName) {
            $data = ArrayHelper::get($dataSection, $mutationName);
            if (! empty($data)) {
                return $data;
            }
        }

        throw new CommerceException('No valid data found in response from relationship updates.');
    }
}
