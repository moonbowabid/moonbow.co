<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class GoogleAnalyticsFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'google_analytics';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Google analytics', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s and %3$s - <a> tags for plugin links, %2$s and %4$s - </a> tags */
            __('Track advanced eCommerce events and more with Google Analytics. This feature replaces the %1$sGoogle Analytics%2$s and %3$sGoogle Analytics Pro%4$s plugins.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-google-analytics/" target="_blank">',
            '</a>',
            '<a href="https://woocommerce.com/products/woocommerce-google-analytics-pro/" target="_blank">',
            '</a>'
        );
    }

    /**
     * Gets the settings URL.
     */
    public function getSettingsUrl() : string
    {
        return admin_url('admin.php?page=wc-settings&tab=integration&section=google_analytics_pro');
    }
}
