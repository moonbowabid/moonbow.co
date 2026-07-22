<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class ShipmentTrackingFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'shipment_tracking';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Shipment tracking', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Share shipment tracking information with your customers. Open one of your Processing orders to get started. This feature replaces the %1$sShipment Tracking%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/shipment-tracking/" target="_blank">',
            '</a>'
        );
    }
}
