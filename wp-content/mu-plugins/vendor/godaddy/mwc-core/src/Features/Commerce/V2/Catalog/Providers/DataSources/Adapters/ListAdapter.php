<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\DataObjects\ListObject;

class ListAdapter implements DataSourceAdapterContract
{
    /**
     * @throws AdapterException
     */
    public function convertToSource(?Term $term = null) : ListObject
    {
        if (! $term instanceof Term) {
            throw new AdapterException('Missing required Term object.');
        }

        return new ListObject([
            'label'           => $term->getLabel(),
            'name'            => $term->getName(),
            'description'     => strip_tags($term->getDescription()),
            'htmlDescription' => $term->getDescription(),
            'status'          => 'ACTIVE',
        ]);
    }

    /**
     * Converts a {@see ListObject} to a {@see Term} model.
     *
     * @param ListObject|null $list The ListObject to convert.
     *
     * @return Term The converted Term object.
     * @throws AdapterException
     */
    public function convertFromSource(?ListObject $list = null) : Term
    {
        if (! $list instanceof ListObject) {
            throw new AdapterException('Missing required ListObject.');
        }

        $taxonomy = Taxonomy::getNewInstance()->setName(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY);
        $description = $list->htmlDescription ?: $list->description;

        return Term::getNewInstance($taxonomy)
            ->setName($list->name)
            ->setLabel($list->label)
            ->setDescription($description ?? '');
    }
}
