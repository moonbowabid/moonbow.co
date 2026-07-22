<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;

/**
 * Data object representing remote IDs for Commerce Catalog v2 resources.
 *
 * Used to track which remote resources exist for a local product.
 */
class RemoteProductIds extends AbstractDataObject
{
    /** @var non-empty-string|null Remote SKU Group UUID */
    public ?string $skuGroupId = null;

    /** @var non-empty-string|null Remote SKU UUID */
    public ?string $skuId = null;

    /**
     * Creates a new RemoteIds data object.
     *
     * @param array{
     *     skuGroupId?: non-empty-string|null,
     *     skuId?: non-empty-string|null,
     * } $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Checks if a SKU Group ID exists.
     *
     * @return bool
     * @phpstan-assert-if-true non-empty-string $this->skuGroupId
     */
    public function hasSkuGroupId() : bool
    {
        return ! empty($this->skuGroupId);
    }

    /**
     * Checks if a SKU ID exists.
     *
     * @return bool
     * @phpstan-assert-if-true non-empty-string $this->skuId
     */
    public function hasSkuId() : bool
    {
        return ! empty($this->skuId);
    }

    /**
     * Checks if both IDs exist (fully mapped state).
     *
     * @return bool
     * @phpstan-assert-if-true non-empty-string $this->skuGroupId
     * @phpstan-assert-if-true non-empty-string $this->skuId
     */
    public function hasAllIds() : bool
    {
        return $this->hasSkuGroupId() && $this->hasSkuId();
    }
}
