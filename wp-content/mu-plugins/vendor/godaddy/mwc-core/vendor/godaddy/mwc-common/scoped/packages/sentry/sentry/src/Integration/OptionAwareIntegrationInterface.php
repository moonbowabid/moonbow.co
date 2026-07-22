<?php

declare (strict_types=1);
namespace GoDaddy\WordPress\MWC\Common\Vendor\Sentry\Integration;

use GoDaddy\WordPress\MWC\Common\Vendor\Sentry\Options;
interface OptionAwareIntegrationInterface extends IntegrationInterface
{
    /**
     * Sets the options for the integration, is called before `setupOnce()`.
     */
    public function setOptions(Options $options): void;
}
