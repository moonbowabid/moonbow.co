<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class UrlCouponsFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'url_coupons';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Discount links', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Share discount links with your customers and add coupons from ads, email campaigns, or social links. Create or edit a coupon to get started. This feature replaces the %1$sURL Coupons%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/url-coupons/" target="_blank">',
            '</a>'
        );
    }
}
