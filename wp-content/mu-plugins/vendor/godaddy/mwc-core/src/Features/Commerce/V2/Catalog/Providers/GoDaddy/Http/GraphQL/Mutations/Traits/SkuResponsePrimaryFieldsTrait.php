<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits;

trait SkuResponsePrimaryFieldsTrait
{
    /**
     * Gets the primary SKU response fields without nested SKU Group.
     *
     * @return string
     */
    protected function getSkuResponsePrimaryFields() : string
    {
        return '
            id
            name
            label
            code
            description
            htmlDescription
            status
            upcCode
            gtinCode
            weight
            unitOfWeight
            prices {
                edges {
                    node {
                        id
                        compareAtValue {
                            currencyCode
                            value
                        }
                        value {
                            currencyCode
                            value
                        }
                    }
                }
            }
            disableShipping
            disableInventoryTracking
            backorderLimit
            archivedAt
            createdAt
            updatedAt
            mediaObjects {
                edges {
                    node {
                        id
                        name
                        label
                        type
                        url
                        position
                    }
                }
            }
            attributes {
                edges {
                    node {
                        id
                        name
                        label
                        position
                        values {
                            edges {
                                node {
                                    id
                                    name
                                    label
                                    position
                                }
                            }
                        }
                    }
                }
            }
            attributeValues {
                edges {
                    node {
                        id
                        name
                        label
                        position
                    }
                }
            }
            commerceAppsMetafields: metafields(first: 100, namespace: "commerce-apps") {
                edges {
                    node {
                        namespace
                        key
                        value
                        type
                    }
                }
            }
            catalogV1Metafields: metafields(first: 100, namespace: "catalog-v1-product") {
                edges {
                    node {
                        namespace
                        key
                        value
                        type
                    }
                }
            }';
    }
}
