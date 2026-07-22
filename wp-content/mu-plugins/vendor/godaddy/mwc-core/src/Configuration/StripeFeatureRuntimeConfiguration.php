<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class StripeFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'stripe';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Stripe', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return __('Accept credit card payments using Stripe.', 'mwc-core');
    }

    /**
     * Gets the settings URL.
     */
    public function getSettingsUrl() : string
    {
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=stripe');
    }
}
