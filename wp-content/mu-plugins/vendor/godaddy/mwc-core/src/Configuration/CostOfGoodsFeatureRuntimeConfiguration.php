<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

class CostOfGoodsFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration
{
    /** @var string Feature name identifier */
    protected string $featureName = 'cost_of_goods';

    /**
     * Gets the feature name (translated).
     */
    public function getName() : string
    {
        return __('Cost of goods', 'mwc-core');
    }

    /**
     * Gets the feature description (translated).
     */
    public function getDescription() : string
    {
        return sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Track profit and cost of goods for your store. Generate profit reports by date, product, category, and more. This feature replaces the %1$sCost of Goods%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-cost-of-goods/" target="_blank">',
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
