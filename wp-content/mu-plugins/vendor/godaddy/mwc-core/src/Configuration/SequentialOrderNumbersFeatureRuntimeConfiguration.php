<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class SequentialOrderNumbersFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'sequential_order_numbers';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Sequential order numbers', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Format order numbers, change your starting number, and differentiate free orders. This feature replaces the %1$sSequential Order Numbers Pro%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/sequential-order-numbers-pro/" target="_blank">',
            '</a>'
        );
    }

    /**
     * Gets the settings URL.
     */
    public function getSettingsUrl() : string
    {
        return admin_url('admin.php?page=wc-settings&tab=orders');
    }
}
