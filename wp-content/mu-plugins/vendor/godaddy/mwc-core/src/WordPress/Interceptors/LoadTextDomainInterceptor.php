<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * Interceptor to load plugin text domains at the correct time.
 */
class LoadTextDomainInterceptor extends AbstractInterceptor
{
    /**
     * Determines whether the interceptor should be loaded.
     */
    public static function shouldLoad() : bool
    {
        return ! WordPressRepository::isCliMode();
    }

    /**
     * Adds the action hooks.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'loadTextDomains'])
            ->execute();
    }

    /**
     * Loads the plugin's text domains.
     */
    public function loadTextDomains() : void
    {
        $coreDir = plugin_basename(dirname(__DIR__, 3));

        load_plugin_textdomain('mwc-core', false, $coreDir.'/languages');
        load_plugin_textdomain('mwc-common', false, $coreDir.'/vendor/godaddy/mwc-common/languages');
    }
}
