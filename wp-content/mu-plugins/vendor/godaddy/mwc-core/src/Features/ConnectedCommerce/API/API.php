<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\API;

use GoDaddy\WordPress\MWC\Common\API\API as BaseAPI;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\API\Controllers\Store\ConnectionController;

/**
 * API component for the ConnectedCommerce feature.
 */
class API extends BaseAPI
{
    use HasComponentsFromContainerTrait;

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [
        ConnectionController::class,
    ];
}
