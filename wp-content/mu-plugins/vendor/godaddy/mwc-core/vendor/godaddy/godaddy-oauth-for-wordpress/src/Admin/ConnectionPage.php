<?php

namespace GoDaddy\WordPress\OAuth\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\OAuth\Interceptors\DisconnectInterceptor;
use GoDaddy\WordPress\OAuth\Services\TokenService;
use GoDaddy\WordPress\OAuth\Storage\Contracts\TokenRepositoryContract;

/**
 * GoDaddy Connection admin page.
 *
 * Provides WordPress admin interface for managing the OAuth connection.
 * Shows connection status and allows users to connect/reconnect.
 */
class ConnectionPage implements ConditionalComponentContract
{
    /**
     * Admin page slug.
     *
     * @var string
     */
    public const PAGE_SLUG = 'godaddy-connection';

    /**
     * Nonce action for authorization requests.
     *
     * @var string
     */
    public const NONCE_ACTION = 'gd_oauth_authorize';

    /**
     * Token repository instance.
     *
     * @var TokenRepositoryContract
     */
    private TokenRepositoryContract $tokenRepository;

    /**
     * Token service for managing token state.
     *
     * @var TokenService
     */
    private TokenService $tokenService;

    /**
     * Constructor.
     *
     * @param TokenRepositoryContract $tokenRepository Repository instance
     * @param TokenService $tokenService Token service instance
     */
    public function __construct(TokenRepositoryContract $tokenRepository, TokenService $tokenService)
    {
        $this->tokenRepository = $tokenRepository;
        $this->tokenService = $tokenService;
    }

    /** @var string Option key for storing pending notice data */
    public const PENDING_NOTICE_OPTION = 'gd_oauth_pending_notice';

    /**
     * Determines whether the component should be loaded.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return WordPressRepository::isAdmin();
    }

    /**
     * Load the admin page.
     *
     * Registers the admin_menu hook and admin_init hook for pending notices.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        Register::action()
            ->setGroup('admin_menu')
            ->setHandler([$this, 'addMenuPage'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'enqueuePendingNotice'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'enqueueExpirationWarning'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'enqueueExpiredNotice'])
            ->execute();
    }

    /**
     * Enqueue a pending notice stored in wp_options.
     *
     * Reads notice data saved by interceptor handlers, deletes it from
     * wp_options, and enqueues it into the mwc-common Notices system
     * for a single render. The notice displays once and is not dismissible.
     *
     * @return void
     */
    public function enqueuePendingNotice() : void
    {
        $noticeData = get_option(self::PENDING_NOTICE_OPTION);

        if (! is_array($noticeData) || empty($noticeData['id'])) {
            return;
        }

        delete_option(self::PENDING_NOTICE_OPTION);

        Notices::enqueueAdminNotice(
            Notice::getNewInstance()
                ->setId(TypeHelper::string($noticeData['id'], ''))
                ->setType(TypeHelper::string($noticeData['type'] ?? Notice::TYPE_INFO, Notice::TYPE_INFO))
                ->setContent(TypeHelper::string($noticeData['content'] ?? '', ''))
                ->setDismissible(false)
        );
    }

    /**
     * Enqueue expiration warning when in degraded state.
     *
     * Shows a non-dismissible warning with a link to reconnect when the
     * refresh token is invalid but the access token is still valid.
     *
     * @return void
     */
    public function enqueueExpirationWarning() : void
    {
        if (! $this->tokenService->isConnectionDegraded()) {
            return;
        }

        $token = $this->tokenRepository->get();

        if ($token === null || $token->hasExpired()) {
            return;
        }

        $reconnectUrl = $this->getAuthorizationUrl();

        $content = sprintf(
            /* translators: 1: opening anchor tag, 2: closing anchor tag */
            __('Your GoDaddy connection will expire soon. %1$sReconnect now%2$s to maintain access.', 'godaddy-oauth-for-wordpress'),
            '<a href="'.esc_url($reconnectUrl).'">',
            '</a>'
        );

        Notices::enqueueAdminNotice(
            Notice::getNewInstance()
                ->setId('gd-oauth-expiration-warning')
                ->setType(Notice::TYPE_WARNING)
                ->setContent($content)
                ->setDismissible(false)
        );
    }

    /**
     * Enqueue an error notice when the stored token has expired.
     *
     * Fires on every admin page so a user who never visits the Connection
     * page still sees that their connection is dead and needs reconnection.
     * Does not depend on the degraded flag - any expired token triggers it.
     *
     * @return void
     */
    public function enqueueExpiredNotice() : void
    {
        $token = $this->tokenRepository->get();

        if ($token === null || ! $token->hasExpired()) {
            return;
        }

        $reconnectUrl = $this->getAuthorizationUrl();

        $content = sprintf(
            /* translators: 1: opening anchor tag, 2: closing anchor tag */
            __('Your GoDaddy connection has expired. %1$sReconnect%2$s to restore access.', 'godaddy-oauth-for-wordpress'),
            '<a href="'.esc_url($reconnectUrl).'">',
            '</a>'
        );

        Notices::enqueueAdminNotice(
            Notice::getNewInstance()
                ->setId('gd-oauth-expired')
                ->setType(Notice::TYPE_ERROR)
                ->setContent($content)
                ->setDismissible(false)
        );
    }

    /**
     * Add the menu page to WordPress admin Settings submenu.
     *
     * @return void
     */
    public function addMenuPage() : void
    {
        add_options_page(
            __('GoDaddy Connection', 'godaddy-oauth-for-wordpress'),
            __('GoDaddy Connection', 'godaddy-oauth-for-wordpress'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    /**
     * Render the admin page.
     *
     * @return void
     */
    public function renderPage() : void
    {
        $status = $this->getConnectionStatus();

        $this->renderTemplate($status);
    }

    /**
     * Get the current connection status.
     *
     * @return ConnectionStatus
     */
    public function getConnectionStatus() : ConnectionStatus
    {
        return new ConnectionStatus(
            $this->tokenRepository->get(),
            $this->tokenService->isConnectionDegraded()
        );
    }

    /**
     * Get the authorization URL with nonce.
     *
     * @return string The authorization URL with security nonce
     */
    public function getAuthorizationUrl() : string
    {
        $baseUrl = admin_url('admin-post.php?action=gd_oauth_authorize');

        return wp_nonce_url($baseUrl, self::NONCE_ACTION);
    }

    /**
     * Get the GoDaddy customer ID if available.
     *
     * Extracts the customer ID from the JWT access token's `sub` claim.
     * Returns null when no token is stored or the token has no customer ID.
     *
     * @return string|null Customer ID or null if not available
     */
    protected function getCustomerId() : ?string
    {
        $token = $this->tokenRepository->get();

        if (! $token) {
            return null;
        }

        return $token->getCustomerId();
    }

    /**
     * Render the page template.
     *
     * @param ConnectionStatus $status The connection status
     * @return void
     */
    protected function renderTemplate(ConnectionStatus $status) : void
    {
        ?>
        <style>
            .wrap .card.gd-oauth-card {
                max-width: 100%;
                padding: 0;
            }
            .wrap .card.gd-oauth-card .gd-oauth-card-section {
                padding: 0.7em 2em 1em;
            }
            .wrap .card.gd-oauth-card .gd-oauth-card-section + .gd-oauth-card-section {
                border-top: 1px solid #c3c4c7;
            }
            .wrap .card.gd-oauth-card .gd-oauth-detail {
                padding: 8px 0;
            }

            .gd-oauth-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .gd-oauth-modal {
                background: #fff;
                border-radius: 4px;
                width: 480px;
                max-width: 90%;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .gd-oauth-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 24px;
            }

            .gd-oauth-modal-header h2 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                line-height: 1.4;
            }

            .gd-oauth-modal-close {
                background: #f0f0f1;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                color: #50575e;
                padding: 6px;
                line-height: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .gd-oauth-modal-close:hover {
                background: #dcdcde;
                color: #1d2327;
            }

            .gd-oauth-modal-body {
                padding: 16px 24px;
            }

            .gd-oauth-modal-body p {
                margin: 0;
                color: #50575e;
                font-size: 14px;
                line-height: 1.6;
            }

            .gd-oauth-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                padding: 16px 24px;
            }

            .gd-oauth-modal-footer .gd-oauth-modal-cancel,
            .gd-oauth-modal-footer .gd-oauth-modal-confirm {
                border-radius: 4px;
                padding: 10px 24px;
                min-height: 40px;
                font-size: 14px;
                line-height: 1.5;
            }

            .gd-oauth-modal-footer .gd-oauth-modal-cancel {
                background: #fff;
                border: 1px solid #c3c4c7;
                color: #1d2327;
            }

            .gd-oauth-modal-footer .gd-oauth-modal-cancel:hover {
                background: #f6f7f7;
                border-color: #8c8f94;
                color: #1d2327;
            }

            .gd-oauth-modal-footer .gd-oauth-modal-confirm {
                background: #1d2327;
                border: 1px solid #1d2327;
                color: #fff;
                text-decoration: none;
            }

            .gd-oauth-modal-footer .gd-oauth-modal-confirm:hover {
                background: #2c3338;
                border-color: #2c3338;
                color: #fff;
            }

            .gd-oauth-modal-footer .gd-oauth-modal-confirm:focus {
                background: #1d2327;
                border-color: #1d2327;
                color: #fff;
                box-shadow: 0 0 0 1px #1d2327;
            }
        </style>
        <div class="wrap">
            <h1><?php echo esc_html__('GoDaddy Connection', 'godaddy-oauth-for-wordpress'); ?></h1>

            <?php if ($status->isValid()) : ?>
                <?php $this->renderConnectedCard(); ?>
            <?php elseif ($status->isConnected()) : ?>
                <?php $this->renderExpiredCard(); ?>
            <?php else : ?>
                <?php $this->renderDisconnectedCard(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the disconnected state card.
     *
     * @return void
     */
    protected function renderDisconnectedCard() : void
    {
        ?>
        <div class="card gd-oauth-card">
            <div class="gd-oauth-card-section">
                <h2><?php echo esc_html__('Authorize GoDaddy', 'godaddy-oauth-for-wordpress'); ?></h2>
                <p>
                    <?php echo esc_html__(
                        'Authorize GoDaddy to securely connect your WordPress site with your Commerce account so you can manage products and orders in one place. No passwords are shared.',
                        'godaddy-oauth-for-wordpress'
                    ); ?>
                </p>
            </div>
            <div class="gd-oauth-card-section">
                <p>
                    <a href="<?php echo esc_url($this->getAuthorizationUrl()); ?>" class="button button-primary">
                        <?php echo esc_html__('Start Authorization', 'godaddy-oauth-for-wordpress'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the expired state card.
     *
     * @return void
     */
    protected function renderExpiredCard() : void
    {
        ?>
        <div class="card gd-oauth-card">
            <div class="gd-oauth-card-section">
                <h2><?php echo esc_html__('Connection Expired', 'godaddy-oauth-for-wordpress'); ?></h2>
                <p>
                    <?php echo esc_html__(
                        'Your GoDaddy connection has expired and can no longer refresh automatically. Reconnect to continue syncing your store.',
                        'godaddy-oauth-for-wordpress'
                    ); ?>
                </p>
            </div>
            <div class="gd-oauth-card-section">
                <p>
                    <a href="<?php echo esc_url($this->getAuthorizationUrl()); ?>" class="button button-primary">
                        <?php echo esc_html__('Reconnect', 'godaddy-oauth-for-wordpress'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the connected state card.
     *
     * @return void
     */
    protected function renderConnectedCard() : void
    {
        $customerId = $this->getCustomerId();
        ?>
        <div class="card gd-oauth-card">
            <div class="gd-oauth-card-section">
                <h2><?php echo esc_html__('Connected Account Details', 'godaddy-oauth-for-wordpress'); ?></h2>
                <?php if ($customerId) : ?>
                    <p class="gd-oauth-detail">
                        <?php
                        printf(
                            /* translators: %s: Customer ID */
                            esc_html__('Customer ID - %s', 'godaddy-oauth-for-wordpress'),
                            esc_html($customerId)
                        );
                    ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="gd-oauth-card-section">
                <p>
                    <a href="<?php echo esc_url(DisconnectInterceptor::getDisconnectUrl()); ?>"
                       id="gd-oauth-disconnect-btn"
                       class="button">
                        <?php echo esc_html__('Disconnect GoDaddy Account', 'godaddy-oauth-for-wordpress'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
        $this->renderDisconnectModal();
    }

    /**
     * Render the disconnect confirmation modal dialog.
     *
     * @return void
     */
    protected function renderDisconnectModal() : void
    {
        ?>
        <div id="gd-oauth-disconnect-modal"
             class="gd-oauth-modal-overlay"
             role="dialog"
             aria-modal="true"
             aria-labelledby="gd-oauth-modal-title"
             style="display:none;">
            <div class="gd-oauth-modal">
                <div class="gd-oauth-modal-header">
                    <h2 id="gd-oauth-modal-title">
                        <?php echo esc_html__('Disconnect GoDaddy Account?', 'godaddy-oauth-for-wordpress'); ?>
                    </h2>
                    <button type="button"
                            class="gd-oauth-modal-close"
                            aria-label="<?php echo esc_attr__('Close', 'godaddy-oauth-for-wordpress'); ?>">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                            <line x1="4" y1="4" x2="12" y2="12"/><line x1="12" y1="4" x2="4" y2="12"/>
                        </svg>
                    </button>
                </div>
                <div class="gd-oauth-modal-body">
                    <p>
                        <?php echo esc_html__(
                            'Are you sure you want to disconnect your GoDaddy account? Once disconnected, your orders and inventory will no longer sync with your GoDaddy store.',
                            'godaddy-oauth-for-wordpress'
                        ); ?>
                    </p>
                </div>
                <div class="gd-oauth-modal-footer">
                    <button type="button" class="button gd-oauth-modal-cancel">
                        <?php echo esc_html__('Cancel', 'godaddy-oauth-for-wordpress'); ?>
                    </button>
                    <a href="<?php echo esc_url(DisconnectInterceptor::getDisconnectUrl()); ?>"
                       class="button gd-oauth-modal-confirm">
                        <?php echo esc_html__('Confirm Disconnect', 'godaddy-oauth-for-wordpress'); ?>
                    </a>
                </div>
            </div>
        </div>
        <script>
            (function() {
                var overlay = document.getElementById('gd-oauth-disconnect-modal');
                if (!overlay) return;

                var closeBtn = overlay.querySelector('.gd-oauth-modal-close');
                var cancelBtn = overlay.querySelector('.gd-oauth-modal-cancel');
                var openBtn = document.getElementById('gd-oauth-disconnect-btn');
                var previousFocus = null;

                function openModal(e) {
                    e.preventDefault();
                    previousFocus = document.activeElement;
                    overlay.style.display = 'flex';
                    closeBtn.focus();
                }

                function closeModal() {
                    overlay.style.display = 'none';
                    if (previousFocus) {
                        previousFocus.focus();
                    }
                }

                if (openBtn) {
                    openBtn.addEventListener('click', openModal);
                }

                closeBtn.addEventListener('click', closeModal);
                cancelBtn.addEventListener('click', closeModal);

                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && overlay.style.display === 'flex') {
                        closeModal();
                    }
                });

                overlay.addEventListener('keydown', function(e) {
                    if (e.key !== 'Tab') return;

                    var focusable = overlay.querySelectorAll(
                        'button, a[href], [tabindex]:not([tabindex="-1"])'
                    );
                    if (focusable.length === 0) return;

                    var first = focusable[0];
                    var last = focusable[focusable.length - 1];

                    if (e.shiftKey) {
                        if (document.activeElement === first) {
                            e.preventDefault();
                            last.focus();
                        }
                    } else {
                        if (document.activeElement === last) {
                            e.preventDefault();
                            first.focus();
                        }
                    }
                });
            })();
        </script>
        <?php
    }
}
