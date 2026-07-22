<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class ShippingFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'shipping';

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return __('Take administration of order fulfillment to the next level! View latest shipping rates from your favorite providers, print labels in one click, and automate shipment tracking and customer email notifications.', 'mwc-core');
    }
}
