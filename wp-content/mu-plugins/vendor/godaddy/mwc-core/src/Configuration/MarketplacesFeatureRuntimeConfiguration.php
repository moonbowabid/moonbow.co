<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class MarketplacesFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'marketplaces';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Marketplaces', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return __('Sell to millions of customers from one place. Offer your products everywhere, from Amazon to Instagram, all from your own online store.', 'mwc-core');
    }

    /**
     * Gets the settings URL.
     */
    public function getSettingsUrl() : string
    {
        return admin_url('admin.php?page=gd-marketplaces');
    }
}
