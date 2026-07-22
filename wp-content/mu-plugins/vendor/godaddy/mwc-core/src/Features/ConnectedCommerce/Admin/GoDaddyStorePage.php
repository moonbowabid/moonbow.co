<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ConnectedCommerce\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\DelayedInstantiationComponentContract;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\GoDaddy\Contracts\ThreeLeggedOAuthTokenProviderContract;
use GoDaddy\WordPress\MWC\Core\Pages\Traits\CanHideWordPressAdminNoticesTrait;
use Throwable;

/**
 * GoDaddy Store settings page.
 *
 * This class implements {@see DelayedInstantiationComponentContract} to ensure that a new instance
 * is created after the OAuth package has been loaded and its components are available in the DI container.
 * It additionally implements {@see ConditionalComponentContract} to decline loading when the OAuth token
 * provider contract cannot be resolved — on misconfigured sites the admin page would otherwise fatal
 * via HasComponentsFromContainerTrait's no-arg fallback (see MWC-19586).
 */
class GoDaddyStorePage extends AbstractAdminPage implements DelayedInstantiationComponentContract, ConditionalComponentContract
{
    use CanHideWordPressAdminNoticesTrait;

    /** @var string the page and menu item slug */
    public const SLUG = 'godaddy-store';

    /** @var string parent menu item identifier */
    public const PARENT_MENU_ITEM = 'options-general.php';

    /** @var string required capability to interact with page and related menu item */
    public const CAPABILITY = 'manage_woocommerce';

    protected ThreeLeggedOAuthTokenProviderContract $authProvider;

    /**
     * Schedules instantiation on the init hook to ensure OAuth dependencies are available.
     */
    public static function scheduleInstantiation(callable $callback) : void
    {
        try {
            Register::action()
                ->setGroup('init')
                ->setHandler($callback)
                ->execute();
        } catch (Exception $exception) {
            // silently fail if we can't register the hook
        }
    }

    /**
     * Determines whether this component should load.
     *
     * Returns false on sites where the OAuth token provider contract cannot be resolved so that
     * the admin page is skipped rather than fataling on its required constructor argument (MWC-19586).
     */
    public static function shouldLoad() : bool
    {
        try {
            ContainerFactory::getInstance()->getSharedContainer()->get(ThreeLeggedOAuthTokenProviderContract::class);

            return true;
        } catch (Throwable $throwable) {
            return false;
        }
    }

    /**
     * Initializes the GoDaddy Store page.
     */
    public function __construct(ThreeLeggedOAuthTokenProviderContract $authProvider)
    {
        $this->authProvider = $authProvider;

        $this->screenId = static::SLUG;
        $this->title = __('GoDaddy Store', 'mwc-core');
        $this->menuTitle = __('GoDaddy Store', 'mwc-core');
        $this->parentMenuSlug = static::PARENT_MENU_ITEM;
        $this->capability = static::CAPABILITY;

        parent::__construct();
    }

    /**
     * Initializes the GoDaddy Store admin page.
     *
     * @throws Exception
     */
    public function load() : void
    {
        $this->registerHooks();
    }

    /**
     * Registers hooks.
     *
     * @throws Exception
     */
    protected function registerHooks() : void
    {
        Register::action()
            ->setGroup('load-settings_page_'.static::SLUG)
            ->setHandler([$this, 'hideAdminNotices'])
            ->execute();

        Register::filter()
            ->setGroup('load-settings_page_'.static::SLUG)
            ->setHandler([$this, 'registerAdminHooks'])
            ->execute();
    }

    /**
     * Registers admin hooks.
     *
     * @internal
     * @see GoDaddyStorePage::registerHooks()
     *
     * @throws Exception
     */
    public function registerAdminHooks() : void
    {
        Register::filter()
            ->setGroup('admin_footer_text')
            ->setHandler([$this, 'removeDefaultAdminFooter'])
            ->execute();
    }

    /**
     * Removes the default footer from the admin page.
     *
     * @internal
     */
    public function removeDefaultAdminFooter() : string
    {
        return '';
    }

    /**
     * Determines whether the menu item for the page should be added.
     *
     * @internal
     * @see AbstractAdminPage::registerMenuItem()
     */
    public function shouldAddMenuItem() : bool
    {
        return current_user_can($this->getCapability()) && $this->hasOAuthCredentials();
    }

    /**
     * Determines whether the user has valid OAuth credentials.
     */
    protected function hasOAuthCredentials() : bool
    {
        try {
            $this->authProvider->getCredentials();

            return true;
        } catch (CredentialsCreateFailedException $exception) {
            return false;
        }
    }

    /**
     * Renders the GoDaddy Store page HTML.
     *
     * @internal
     * @see GoDaddyStorePage::addMenuItem()
     */
    public function render() : void
    {
        ?>
        <div class="wrap">
            <div id="mwc-store-selector-screen"></div>
        </div>
        <?php
    }
}
