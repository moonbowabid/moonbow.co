<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject as CommonAbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

/**
 * Maintains an association between a local WooCommerce resource ID and remote resource object (e.g. {@see ProductBase}).
 *
 * @method static static getNewInstance(array $data)
 */
class AbstractResourceAssociation extends CommonAbstractDataObject
{
    /** @var CommonAbstractDataObject The remote resource */
    public CommonAbstractDataObject $remoteResource;

    /** @var int Local WooCommerce ID that corresponds to the above remote entity */
    public int $localId;
}
