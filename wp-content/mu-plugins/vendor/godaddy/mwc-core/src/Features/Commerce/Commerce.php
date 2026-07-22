<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Configuration\DisableIncompatibleFeaturesAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\PaymentSettingsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\LocationsIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\OrdersIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanHandleWordPressDatabaseExceptionTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasCommerceCapabilitiesTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Traits\CanDetermineWhetherIsStagingSiteTrait;

class Commerce extends AbstractFeature
{
    use HasComponentsTrait;
    use CanHandleWordPressDatabaseExceptionTrait;
    use CanDetermineWhetherIsStagingSiteTrait;
    use HasCommerceCapabilitiesTrait;

    public const CAPABILITY_READ = 'read';
    public const CAPABILITY_WRITE = 'write';
    public const CAPABILITY_EVENTS = 'events';
    public const CAPABILITY_DETECT_UPSTREAM_CHANGES = 'detect_upstream_changes';

    /** @var string transient that disables the feature */
    public const TRANSIENT_DISABLE_FEATURE = 'godaddy_mwc_commerce_disabled';

    /**
     * List of components to load.
     *
     * The list is separated in two groups by priority and the classes in each group are ordered alphabetically.
     *
     * @var class-string<ComponentContract>[]
     */
    protected array $componentClasses = [
        // these components should be loaded first
        CreateCommerceContextsTableAction::class,
        CreateCommerceMapResourceTypesTableAction::class,
        CreateCommerceMapIdsTableAction::class,
        CreateCommerceSkippedResourcesTableAction::class,
        InsertResourceTypesAction::class,
        CreateCommerceResourceUpdatesTableAction::class,

        // integrations
        CatalogIntegration::class,
        CustomersIntegration::class,
        InventoryIntegration::class,
        LocationsIntegration::class,
        OrdersIntegration::class,

        // misc.
        DisableIncompatibleFeaturesAction::class,
        PaymentSettingsInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        if (get_transient(static::TRANSIENT_DISABLE_FEATURE)) {
            return false;
        }

        if (! static::getStoreId()) {
            return false;
        }

        if (! static::hasHostingPlanRestriction() && ! static::storeIdsAreAligned()) {
            return false;
        }

        if (static::isStagingSite()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * Determines whether the feature is configured to run on specific hosting plans only.
     */
    protected static function hasHostingPlanRestriction() : bool
    {
        $allowedHostingPlans = TypeHelper::array(static::getConfiguration('allowedHostingPlans', []), []);

        return ! empty($allowedHostingPlans);
    }

    /**
     * Determines whether the Commerce default Store ID matches the configured Store ID (and the Payments-side Store ID when set).
     *
     * This guard is only meaningful once the platform has lifted Commerce's hosting-plan restriction — callers are
     * expected to skip it while {@see static::hasHostingPlanRestriction()} still returns `true`, because the platform
     * is gating access in that case. Once the restriction is lifted, the Connected Commerce flow expects the Commerce
     * feature to target the same Store as the platform default — and the same Store as GoDaddy Payments when GoDaddy
     * Payments is connected. If the customer picks a new Store while the previous Store ID is still configured,
     * Commerce would otherwise start syncing records from the wrong Store until the scheduled actions catch up.
     */
    protected static function storeIdsAreAligned() : bool
    {
        // nothing to compare against; let the existing guards decide
        if (! $defaultStoreId = static::determineDefaultStoreId()) {
            return true;
        }

        if ($defaultStoreId !== static::getStoreId()) {
            return false;
        }

        // GoDaddy Payments is not connected; only the default and configured Store IDs need to agree
        if (! $goDaddyPaymentsStoreId = Poynt::getSiteStoreId()) {
            return true;
        }

        return $defaultStoreId === $goDaddyPaymentsStoreId;
    }

    /**
     * Returns the Commerce default Store ID from the platform's store repository.
     */
    protected static function determineDefaultStoreId() : ?string
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository()->determineDefaultStoreId();
        } catch (PlatformRepositoryException $exception) {
            return null;
        }
    }

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load() : void
    {
        try {
            /** @throws WordPressDatabaseException|BaseException|Exception */
            $this->loadComponents();
        } catch (WordPressDatabaseException $exception) {
            $this->handleWordPressDatabaseException($exception, static::getName(), static::TRANSIENT_DISABLE_FEATURE);
        }
    }

    /**
     * Gets the store's ID.
     *
     * @return string|null
     */
    public static function getStoreId() : ?string
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository()->getStoreId();
        } catch (PlatformRepositoryException $exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, bool>
     */
    public static function getCommerceCapabilities() : array
    {
        /** @var array<string, bool> $capabilities */
        $capabilities = TypeHelper::array(static::getConfiguration('capabilities', []), []);

        return $capabilities;
    }

    /**
     * Gets the channel ID.
     *
     * @return string
     */
    public static function getChannelId() : string
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getChannelId();
        } catch (PlatformRepositoryException $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            return '';
        }
    }
}
