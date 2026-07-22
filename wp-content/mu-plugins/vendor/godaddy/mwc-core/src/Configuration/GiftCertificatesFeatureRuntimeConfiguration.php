<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class GiftCertificatesFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'gift_certificates';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Gift certificates', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Create custom gift certificates that your customers can purchase and send to their friends and family. This feature replaces the %1$sPDF Product Vouchers%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/pdf-product-vouchers/" target="_blank">',
            '</a>'
        );
    }

    /**
     * Gets the settings URL.
     */
    public function getSettingsUrl() : string
    {
        return admin_url('edit.php?post_type=wc_voucher');
    }
}
