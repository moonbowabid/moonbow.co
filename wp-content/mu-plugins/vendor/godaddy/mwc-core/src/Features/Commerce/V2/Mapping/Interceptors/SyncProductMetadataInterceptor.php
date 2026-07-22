<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\V2\Mapping\Interceptors\Handler\SyncProductMetadataHandler;

class SyncProductMetadataInterceptor extends AbstractInterceptor
{
    /** @var string */
    public const JOB_NAME = 'mwc_gd_commerce_sync_product_metadata';

    /**
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::JOB_NAME)
            ->setArgumentsCount(2)
            ->setHandler([SyncProductMetadataHandler::class, 'handle'])
            ->execute();
    }
}
