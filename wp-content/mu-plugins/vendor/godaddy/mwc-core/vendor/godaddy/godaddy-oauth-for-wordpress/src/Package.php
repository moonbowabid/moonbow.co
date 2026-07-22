<?php

namespace GoDaddy\WordPress\OAuth;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\OAuth\Admin\ConnectionPage;
use GoDaddy\WordPress\OAuth\Interceptors\AuthorizationInterceptor;
use GoDaddy\WordPress\OAuth\Interceptors\CallbackInterceptor;
use GoDaddy\WordPress\OAuth\Interceptors\DisconnectInterceptor;
use GoDaddy\WordPress\OAuth\Interceptors\TokenRefreshInterceptor;
use GoDaddy\WordPress\OAuth\Providers\OAuthServiceProvider;

/**
 * Main Package class.
 *
 * Singleton pattern ensures only one instance exists even if multiple
 * plugins try to initialize the package.
 */
class Package
{
    use HasComponentsFromContainerTrait;

    /**
     * Package ID.
     */
    const ID = 'godaddy-oauth-for-wordpress';

    /**
     * Package version.
     */
    const VERSION = '1.0.0';

    /**
     * Filter name for controlling whether the package should initialize its functionality.
     */
    const FILTER_SHOULD_INITIALIZE = 'godaddy_oauth_should_initialize';

    /**
     * Single instance of the package.
     *
     * @var Package|null
     */
    private static ?Package $instance = null;

    /**
     * Whether the package has been initialized.
     *
     * @var bool
     */
    private bool $initialized = false;

    /**
     * Component classes to load.
     *
     * @var class-string<ComponentContract>[]
     */
    protected array $componentClasses = [
        AuthorizationInterceptor::class,
        CallbackInterceptor::class,
        DisconnectInterceptor::class,
        TokenRefreshInterceptor::class,
        ConnectionPage::class,
    ];

    /**
     * Get the singleton instance.
     *
     * @return Package
     */
    public static function instance() : Package
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize the package.
     *
     * @return void
     */
    private function initialize() : void
    {
        if ($this->initialized) {
            return;
        }

        $this->initializeContainer();
        $this->addHooks();

        $this->initialized = true;
    }

    /**
     * Initialize the DI container with service providers.
     *
     * @return void
     */
    private function initializeContainer() : void
    {
        $container = $this->getContainer();

        $container->addProvider(new OAuthServiceProvider());
        $container->enableAutoWiring();
    }

    /**
     * Get the shared DI container.
     *
     * @return ContainerContract
     */
    protected function getContainer() : ContainerContract
    {
        return ContainerFactory::getInstance()->getSharedContainer();
    }

    /**
     * Add necessary action and filter hooks.
     *
     * @return void
     */
    protected function addHooks() : void
    {
        // Hook for future functionality
        add_action('init', [$this, 'onInit']);
    }

    /**
     * Handle WordPress init hook.
     *
     * Loads all registered components, but only when the filter allows initialization.
     *
     * @return void
     */
    public function onInit() : void
    {
        if (! $this->shouldInitialize()) {
            return;
        }

        try {
            $this->loadComponents();
        } catch (Exception $exception) {
            SentryException::getNewInstance("Failed to load components for godaddy-oauth: {$exception->getMessage()}", $exception);
        }
    }

    /**
     * Determines whether the package should initialize its functionality.
     *
     * @return bool
     */
    protected function shouldInitialize() : bool
    {
        return TypeHelper::bool(apply_filters(self::FILTER_SHOULD_INITIALIZE, false), false);
    }

    /**
     * Get package version.
     *
     * @return string
     */
    public function getVersion() : string
    {
        return self::VERSION;
    }

    /**
     * Get package path.
     *
     * @return string
     */
    public static function getPackagePath() : string
    {
        return untrailingslashit(__DIR__);
    }

    /**
     * Get package URL.
     *
     * @return string
     */
    public static function getPackageUrl() : string
    {
        return untrailingslashit(plugins_url('', __FILE__));
    }

    /**
     * Check if package is initialized.
     *
     * @return bool
     */
    public function isInitialized() : bool
    {
        return $this->initialized;
    }
}
