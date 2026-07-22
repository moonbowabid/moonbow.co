<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects\RelationshipUpdates;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\SkuPrice;

class PriceUpdates extends AbstractRelationshipUpdate
{
    /** @var SkuPrice|null The price to be updated, or null if no updates */
    public ?SkuPrice $toUpdate = null;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Check if there are prices to update.
     *
     * @return bool
     * @phpstan-assert-if-true !null $this->toUpdate
     */
    public function hasPricesToUpdate() : bool
    {
        return $this->toUpdate !== null;
    }

    /**
     * Check if there are any updates (required by AbstractRelationshipUpdate).
     *
     * @return bool
     */
    public function hasUpdates() : bool
    {
        return $this->hasPricesToUpdate();
    }

    /**
     * Build GraphQL variables for price updates.
     *
     * @return array<string, mixed>
     */
    public function buildGraphQLVariables() : array
    {
        $variables = [];

        if ($this->hasPricesToUpdate()) {
            $variables['priceId'] = $this->toUpdate->id;
            $variables['priceInput'] = [
                'value'          => $this->toUpdate->value->toArray(),
                'compareAtValue' => $this->toUpdate->compareAtValue ? $this->toUpdate->compareAtValue->toArray() : null,
            ];
        }

        return $variables;
    }

    /**
     * {@inheritDoc}
     */
    public function buildVariableDefinitions() : array
    {
        $definitions = [];

        if ($this->hasPricesToUpdate()) {
            $definitions[] = '$priceId: String!';
            $definitions[] = '$priceInput: MutationUpdateSkuPriceInput!';
        }

        return $definitions;
    }

    /**
     * {@inheritDoc}
     */
    public function buildMutationFragments(string $entityType, string $entityIdVar) : array
    {
        $mutations = [];

        if ($this->hasPricesToUpdate()) {
            $mutations[] = "updatePrice: update{$entityType}Price(id: \$priceId, input: \$priceInput) {
                        id
                        value {
                            currencyCode
                            value
                        }
                        compareAtValue {
                            currencyCode
                            value
                        }
                        createdAt
                        updatedAt
                    }";
        }

        return $mutations;
    }

    /** {@inheritDoc} */
    public function needsEntityIdVariable() : bool
    {
        return false;
    }
}
