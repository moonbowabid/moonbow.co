<?php

return [
    'catalog' => [
        'api' => [
            'timeout' => [
                'prod' => defined('MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT') ? MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT : 20,
                'dev'  => defined('MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT') ? MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT : 20,
            ],
            /*
             * Maximum number of UUIDs to include in an `ids` filter.
             *
             * This value is set based on a combination of factors:
             *
             * 1. If a query string gets too long, the request won't go through. Max UUIDs in this scenario is 100.
             * 2. The API also has its own lower limitation of 50.
             * 3. More than 20 ids per request cause performance to degrade for Stores that are configured to read v1 data from Catalog v2.
             *
             * @link https://godaddy-corp.atlassian.net/browse/MWC-15546
             * @link https://godaddy-corp.atlassian.net/browse/MWC-15544
             */
            'maxIdsPerRequest' => defined('MWC_COMMERCE_CATALOG_SERVICE_MAX_PRODUCTS_IDS_PER_REQUEST') ? MWC_COMMERCE_CATALOG_SERVICE_MAX_PRODUCTS_IDS_PER_REQUEST : 20,
            'products'         => [
                'cache' => [
                    'ttl' => defined('MWC_COMMERCE_CATALOG_SERVICE_PRODUCTS_CACHE_TTL') ? MWC_COMMERCE_CATALOG_SERVICE_PRODUCTS_CACHE_TTL : DAY_IN_SECONDS,
                ],
            ],
        ],
        'website' => [
            'categoriesUrl' => [
                'prod' => 'https://spa.commerce.godaddy.com/categories',
                'dev'  => 'https://spa.commerce.test-godaddy.com/categories',
            ],
        ],
        'assets' => [
            'user' => [
                'login'        => 'gd_commerce_assets',
                'emailAddress' => 'gd-commerce-assets@godaddy.com',
                'displayName'  => 'GoDaddy Commerce Assets',
            ],
            'hideAssetUser' => true,
        ],
    ],
    'inventory' => [
        'api' => [
            'timeout' => [
                'prod' => defined('MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT') ? MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT : 10,
                'dev'  => defined('MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT') ? MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT : 10,
            ],
        ],
    ],
    'godaddy_gateway' => [
        'api' => [
            'url' => [
                'prod' => 'https://api.godaddy.com',
                'dev'  => 'https://api.test-godaddy.com',
            ],
        ],
    ],
    'gateway' => [
        'api' => [
            'url' => [
                'prod' => defined('MWC_COMMERCE_API_GATEWAY_URL') ? MWC_COMMERCE_API_GATEWAY_URL : 'https://api.mwc.secureserver.net',
                'dev'  => defined('MWC_COMMERCE_API_GATEWAY_URL') ? MWC_COMMERCE_API_GATEWAY_URL : 'https://api-test.mwc.secureserver.net',
            ],
            'timeout' => [
                'prod' => defined('MWC_COMMERCE_API_GATEWAY_TIMEOUT') ? MWC_COMMERCE_API_GATEWAY_TIMEOUT : 10,
                'dev'  => defined('MWC_COMMERCE_API_GATEWAY_TIMEOUT') ? MWC_COMMERCE_API_GATEWAY_TIMEOUT : 10,
            ],
        ],
    ],
];
