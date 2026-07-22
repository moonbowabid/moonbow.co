<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class EmailNotificationsFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'email_notifications';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Ecommerce emails', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Customize your emails to reflect your brand and increase customer loyalty. This feature replaces the %1$sWooCommerce Email Customizer%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-email-customizer/" target="_blank">',
            '</a>'
        );
    }

    /**
     * Gets the settings URL.
     */
    public function getSettingsUrl() : string
    {
        return admin_url('admin.php?page=gd-email-notifications&tab=settings');
    }
}
