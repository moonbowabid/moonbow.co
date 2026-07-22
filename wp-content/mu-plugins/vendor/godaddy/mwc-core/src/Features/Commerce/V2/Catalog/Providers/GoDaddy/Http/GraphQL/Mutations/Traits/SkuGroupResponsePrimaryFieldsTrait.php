<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Catalog\Providers\GoDaddy\Http\GraphQL\Mutations\Traits;

/**
 * Trait providing common primary SKU Group response fields for GraphQL operations.
 */
trait SkuGroupResponsePrimaryFieldsTrait
{
    /**
     * Gets the primary SKU Group response fields.
     *
     * @return string
     */
    protected function getSkuGroupResponsePrimaryFields() : string
    {
        return '
            id
            name
            label
            description
            htmlDescription
            status
            type
            channels {
              edges {
                node {
                  id
                  channelId
                }
              }
            }
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
            lists {
                edges {
                    node {
                        id
                        name
                        label
                        description
                        status
                    }
                }
            }
            ';
    }
}
