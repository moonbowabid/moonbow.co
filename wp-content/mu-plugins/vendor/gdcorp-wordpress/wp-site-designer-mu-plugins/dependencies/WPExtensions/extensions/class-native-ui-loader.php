<?php
/**
 * Native UI Loader
 *
 * Loads the Site Designer native UI React app from CDN and injects
 * the configuration needed by the front-end.
 *
 * CDN URL: Config cdn_url from site-designer.json per environment.
 *
 * @see $wp_deps in enqueue_assets() — must match @wordpress/* imports in native-ui.
 *
 * @package wp-site-designer-mu-plugins
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Utils\CDN_Version_Override;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the React native UI assets from CDN and renders the mount point.
 */
class Native_UI_Loader {

	/**
	 * Minimum capability required to access the chat panel.
	 * Uses 'edit_posts' to allow editors and above to use AI features.
	 */
	private const REQUIRED_CAPABILITY = 'edit_posts';

	/**
	 * Plugin configuration.
	 *
	 * @var object
	 */
	private object $config;

	/**
	 * Constructor.
	 *
	 * @param object $config Plugin configuration instance.
	 */
	public function __construct( object $config ) {
		$this->config = $config;
	}

	/**
	 * Initialize the class and register hooks.
	 *
	 * @param object $config Plugin configuration instance.
	 * @return void
	 */
	public static function init( object $config ): void {
		$instance = new self( $config );
		add_action( 'admin_head', array( $instance, 'print_early_panel_class' ), 1 );
		add_action( 'wp_head', array( $instance, 'print_early_panel_class' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_assets' ) );
		add_action( 'admin_bar_menu', array( $instance, 'add_admin_bar_item' ), 80 );
		add_action( 'admin_bar_menu', array( $instance, 'add_version_override_indicator' ), 81 );

		// Add sdui-panel-open class to <html> server-side (like WP adds admin-bar).
		if ( self::is_panel_open() ) {
			add_filter( 'language_attributes', array( $instance, 'add_panel_open_class' ) );
			add_action( 'wp_footer', array( $instance, 'render_panel_placeholder' ) );
			add_action( 'admin_footer', array( $instance, 'render_panel_placeholder' ) );
		}
	}

	/**
	 * Whether the current request is rendering a block-editor screen.
	 *
	 * @return bool
	 */
	private static function is_block_editor_screen(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		return $screen && $screen->is_block_editor();
	}

	/**
	 * Check if the chat panel was left open by reading the cookie set by JS.
	 *
	 * @return bool
	 */
	private static function is_panel_open(): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Boolean cookie check, value not used.
		return isset( $_COOKIE['sdui_panel_open'] ) && '1' === $_COOKIE['sdui_panel_open'];
	}

	/**
	 * Append sdui-panel-open to the <html> language_attributes output,
	 * so the class is in the initial HTML like WP's admin-bar class.
	 *
	 * @param string $output The existing language attributes.
	 * @return string
	 */
	public function add_panel_open_class( string $output ): string {
		if ( strpos( $output, 'class="' ) !== false ) {
			return str_replace( 'class="', 'class="sdui-panel-open ', $output );
		}
		return $output . ' class="sdui-panel-open"';
	}

	/**
	 * Render the panel placeholder div in the footer so it's in the
	 * initial HTML, no JS needed.
	 *
	 * @return void
	 */
	public function render_panel_placeholder(): void {
		echo '<div id="sdui-panel-placeholder"></div>';
	}

	/**
	 * Print an inline script in <head> that restores the panel-open class
	 * on <html> before the first paint, preventing a layout shift when the
	 * panel was left open on the previous page.
	 *
	 * @return void
	 */
	public function print_early_panel_class(): void {
		$ai_action = $this->get_effective_action();
		if ( in_array( $ai_action, array( 'generate', 'migrate', 'refresh' ), true ) ) {
			?>
			<style>html.sdui-fullscreen,html.sdui-fullscreen body{overflow:hidden!important;height:100%}html.sdui-fullscreen body{font-family:"GD Sherpa",Helvetica,Arial,sans-serif!important;background:transparent!important}html.sdui-fullscreen #wpwrap{display:none!important}</style>
			<script>try{localStorage.removeItem('site-designer-ui-panel-open');var d=document.documentElement;d.classList.remove('sdui-panel-open');d.classList.add('sdui-fullscreen')}catch(e){}</script>
			<?php
			return;
		}
		?>
		<style>html.sdui-panel-open body:not(.wp-admin){margin-right:400px}html.sdui-panel-open #wpcontent,html.sdui-panel-open #wpfooter{margin-right:400px}html.sdui-panel-open body:not(.wp-admin):not(.block-editor-page) header.wp-block-template-part{right:400px}#sdui-panel-placeholder{display:none;position:fixed;right:0;top:var(--wp-admin--admin-bar--height,32px);width:400px;height:calc(100dvh - var(--wp-admin--admin-bar--height,32px));background:linear-gradient(135deg,#1a1230 0%,#110c1d 100%);z-index:99999}html.sdui-panel-open #sdui-panel-placeholder{display:block}@media(max-width:768px){html.sdui-panel-open body:not(.wp-admin),html.sdui-panel-open #wpcontent,html.sdui-panel-open #wpfooter{margin-right:0}html.sdui-panel-open #sdui-panel-placeholder{display:none}}</style>
		<script>try{if(!document.documentElement.classList.contains('sdui-panel-open')&&localStorage.getItem('site-designer-ui-panel-open')==='true'){document.documentElement.classList.add('sdui-panel-open');document.cookie='sdui_panel_open=1;path=/;max-age=31536000;SameSite=Lax;Secure';document.addEventListener('DOMContentLoaded',function(){if(!document.getElementById('sdui-panel-placeholder')){var p=document.createElement('div');p.id='sdui-panel-placeholder';document.body.appendChild(p)}})}}catch(e){}</script>
		<?php
	}

	/**
	 * Add an "Airo for WordPress" item to the WordPress admin bar.
	 *
	 * Shown on both wp-admin and the frontend. JavaScript toggles
	 * visibility based on panel state (visible only when fully closed).
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @return void
	 */
	public function add_admin_bar_item( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			return;
		}
		if ( ! get_option( 'wp_site_designer_activated', false ) ) {
			return;
		}

		$icon = '<svg class="sdui-admin-bar-sparkle-icon" width="20" height="20" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
			. '<path d="M12.1642 24.6478C7.3802 20.3751 4.5086 21.8081 4.476 19.9417C4.4434 18.0753 7.3633 19.4073 11.9953 14.9702C16.6273 10.5332 14.6836 7.3172 16.7013 7.282C18.7191 7.2468 16.8888 10.5286 21.6728 14.8013C26.4568 19.074 29.3285 17.641 29.3611 19.5074C29.3937 21.3737 26.4737 20.0418 21.8418 24.4788C17.2098 28.9159 19.1534 32.1319 17.1357 32.1671C15.118 32.2023 16.9482 28.9205 12.1642 24.6478Z" fill="currentColor"/>'
			. '<path d="M28.736 34.63167C25.6606 31.8849 24.2512 32.354 24.2302 31.1542C24.2093 29.9544 25.6342 30.374 28.6119 27.5216C31.5896 24.6692 30.7923 23.0385 32.0894 23.0158C33.3865 22.9932 32.6465 24.6508 35.722 27.3975C38.7974 30.1443 40.2069 29.6752 40.2278 30.875C40.2487 32.0748 38.8238 31.6552 35.8461 34.50757C32.8684 37.35997 33.6657 38.99075 32.3686 39.01339C31.0715 39.03603 31.8115 37.37842 28.736 34.63167Z" fill="currentColor"/>'
			. '<path d="M29.5436 12.9847C27.4933 11.1535 26.5536 11.4662 26.5397 10.6664C26.5257 9.8665 27.4757 10.1462 29.4608 8.2446C31.446 6.343 30.9144 5.2558 31.7791 5.2408C32.6439 5.2257 32.1506 6.3307 34.2009 8.1619C36.2511 9.9931 37.1908 9.6803 37.2047 10.4802C37.2187 11.2801 36.2687 11.0003 34.2836 12.9019C32.2984 14.8035 32.83 15.8907 31.9653 15.9058C31.1005 15.9209 31.5939 14.8158 29.5436 12.9847Z" fill="currentColor"/>'
			. '</svg>';

		$wp_admin_bar->add_node(
			array(
				'id'     => 'sdui-chat',
				'parent' => 'top-secondary',
				'title'  => '<span class="ab-icon" aria-hidden="true">' . $icon . '</span>'
					. '<span class="ab-label">Airo for WordPress</span>'
					. '<span id="sdui-admin-bar-badge" class="sdui-admin-bar-badge"></span>',
				'href'   => '#',
				'meta'   => array(
					'class' => 'sdui-admin-bar-chat',
				),
			)
		);
	}

	/**
	 * Show an admin bar indicator when a CDN version override is active.
	 *
	 * Visible on both admin and frontend pages. Double-gated: only renders
	 * in non-production AND only when a valid override cookie exists.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @return void
	 */
	public function add_version_override_indicator( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( 'production' === $this->config->get_env() ) {
			return;
		}

		$override = CDN_Version_Override::get_active();
		if ( ! $override ) {
			return;
		}

		$clear_url = add_query_arg( CDN_Version_Override::PARAM_NAME, '', remove_query_arg( CDN_Version_Override::PARAM_NAME ) );

		$wp_admin_bar->add_node(
			array(
				'id'     => 'sdui-version-override',
				'parent' => 'top-secondary',
				'title'  => '🔀 ' . esc_html( $override ),
				'href'   => esc_url( $clear_url ),
				'meta'   => array(
					'title' => 'SDUI version override active — click to reset',
				),
			)
		);
	}

	/**
	 * Enqueue chat panel JavaScript and CSS from CDN.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		// Gate access to users who can edit content.
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			return;
		}

		// If site not activated, only load for explicit ai-action requests,
		// including brief=expert which implies generate mode.
		// Lazy load native ui until site is generated through our experience.
		$ai_action = $this->get_effective_action();
		if ( ! get_option( 'wp_site_designer_activated', false ) && ! $ai_action ) {
			return;
		}

		$cdn_base = $this->get_cdn_base_url();
		$version  = $this->get_asset_version();

		if ( ! $cdn_base ) {
			return;
		}

		// Expert brief mode uses the WordPress Media Library for brand assets.
		// wp_enqueue_media() loads the required scripts (wp.media) on admin pages.
		if ( is_admin() && 'expert' === $this->get_brief_mode() ) {
			wp_enqueue_media();
		}

		// Must stay in sync with @wordpress/* runtime imports in packages/native-ui/src/.
		$wp_deps = array(
			'react-jsx-runtime',
			'wp-element',
			'wp-data',
			'wp-hooks',
			'wp-compose',
		);

		// Editor-only deps for the block-level improver; skip on frontend and non-editor admin pages.
		if ( self::is_block_editor_screen() ) {
			$wp_deps[] = 'wp-blocks';
			$wp_deps[] = 'wp-block-editor';
			$wp_deps[] = 'wp-components';
		}

		$manifest = $this->resolve_manifest( $cdn_base );
		$js_file  = $this->get_asset_filename( $manifest, 'native-ui.js' );
		$css_file = $this->get_asset_filename( $manifest, 'native-ui.css' );

		wp_enqueue_script(
			'site-designer-native-ui',
			$cdn_base . '/' . $js_file,
			$wp_deps,
			$version,
			array( 'strategy' => 'defer' )
		);

		add_filter( 'script_loader_tag', array( $this, 'add_load_error_handler' ), 10, 2 );

		wp_enqueue_style(
			'site-designer-native-ui',
			$cdn_base . '/' . $css_file,
			array(),
			$version
		);

		// Inject config as JSON — wp_add_inline_script handles escaping
		// and is semantically correct for structured data (vs wp_localize_script).
		$config = apply_filters( 'wp_site_designer_ui_config', $this->get_config() );
		wp_add_inline_script(
			'site-designer-native-ui',
			'window.siteDesignerChat = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}

	/**
	 * Add an onerror handler to the native-ui script tag so that WordPress
	 * remains fully usable when the JS artifact fails to load (CDN outage,
	 * missing build, network error).
	 *
	 * @param string $tag    The full <script> tag.
	 * @param string $handle The script handle.
	 * @return string Modified tag with onerror fallback.
	 */
	public function add_load_error_handler( string $tag, string $handle ): string {
		if ( 'site-designer-native-ui' !== $handle ) {
			return $tag;
		}

		$cleanup = "document.documentElement.classList.remove('sdui-fullscreen','sdui-panel-open')";

		return str_replace( '<script ', '<script onerror="' . esc_attr( $cleanup ) . '" ', $tag );
	}

	/**
	 * Resolve the CDN base URL for chat panel assets.
	 *
	 * Constructs a versioned URL: {cdn_domain}/{version}
	 * Version is resolved from: query param override (non-prod) > CDN pointer > fallback constant.
	 *
	 * @return string Base URL without trailing slash, or empty string if unavailable.
	 */
	private function get_cdn_base_url(): string {
		$base = $this->config->get_cdn_url();
		if ( ! $base ) {
			return '';
		}

		$version = CDN_Version_Override::resolve( $this->config->get_env() );
		if ( ! $version ) {
			$version = $this->resolve_cdn_version( $base );
		}

		return $version ? $base . '/' . $version : $base;
	}

	/**
	 * Fetch the active version from the CDN pointer file (current-version.json).
	 *
	 * Result is cached in a WordPress transient for 5 minutes.
	 * Falls back to GDMU_SITE_DESIGNER_VERSION constant on failure.
	 *
	 * @param string $cdn_base The bare CDN domain URL.
	 * @return string Version string, or empty string on failure.
	 */
	private function resolve_cdn_version( string $cdn_base ): string {
		$transient_key = 'sdui_native_ui_cdn_version';
		$cached        = get_transient( $transient_key );
		if ( $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			$cdn_base . '/current-version.json',
			array( 'timeout' => 3 )
		);

		if ( is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Local/dev: if the CDN doesn't serve current-version.json,
			// assume a dev server serving assets at root (no version path).
			if ( in_array( $this->config->get_env(), array( 'local', 'development' ), true ) ) {
				return '';
			}

			// TODO: Remove fallback once we have a production version.
			$fallback = defined( 'GDMU_SITE_DESIGNER_VERSION' )
				? 'v' . GDMU_SITE_DESIGNER_VERSION
				: 'v1.0.0';
			set_transient( $transient_key, $fallback, MINUTE_IN_SECONDS );
			return $fallback;
		}

		$body    = json_decode( wp_remote_retrieve_body( $response ), true );
		$version = $body['version'] ?? '';

		if ( $version && preg_match( '/^[a-z0-9v][a-z0-9.\-]*$/', $version ) ) {
			set_transient( $transient_key, $version, 5 * MINUTE_IN_SECONDS );
			return $version;
		}

		return '';
	}

	/**
	 * Fetch the asset manifest for the resolved CDN version.
	 *
	 * Webpack writes build/manifest.json mapping logical names
	 * ('native-ui.js', 'native-ui.css') to content-hashed filenames.
	 * Successful results are cached for 5 minutes; failures are cached for
	 * 1 minute so a CDN outage doesn't trigger a wp_remote_get on every
	 * page load. The transient key incorporates $cdn_base so a CDN/version
	 * flip invalidates the cache.
	 *
	 * @param string $cdn_base Versioned CDN base URL (no trailing slash).
	 * @return array<string, string> Logical name -> hashed filename. Empty on failure.
	 */
	private function resolve_manifest( string $cdn_base ): array {
		$transient_key = 'sdui_native_ui_manifest_' . md5( $cdn_base );
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			$cdn_base . '/manifest.json',
			array( 'timeout' => 3 )
		);

		if ( is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $transient_key, array(), MINUTE_IN_SECONDS );
			return array();
		}

		$decoded  = json_decode( wp_remote_retrieve_body( $response ), true );
		$manifest = self::sanitize_manifest( $decoded );

		// Empty after sanitization (malformed/missing keys) is treated as a
		// failed fetch so we don't hammer the CDN on every page load.
		$ttl = empty( $manifest ) ? MINUTE_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
		set_transient( $transient_key, $manifest, $ttl );

		return $manifest;
	}

	/**
	 * Reduce a decoded manifest payload to logical name -> hashed filename.
	 *
	 * Drops any entry whose key or value isn't a non-empty string, so a
	 * malformed manifest can't produce broken URLs or PHP type warnings.
	 *
	 * @param mixed $decoded Raw json_decode output.
	 * @return array<string, string>
	 */
	private static function sanitize_manifest( $decoded ): array {
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$clean = array();
		foreach ( $decoded as $key => $value ) {
			if ( is_string( $key ) && is_string( $value ) && '' !== $value ) {
				$clean[ $key ] = $value;
			}
		}

		return $clean;
	}

	/**
	 * Resolve a manifest entry, falling back to the literal name.
	 *
	 * The fallback keeps the page loading during in-flight rollouts where
	 * the CDN doesn't yet serve manifest.json (or serves an older build
	 * that emitted unhashed filenames).
	 *
	 * @param array<string, string> $manifest     Result of resolve_manifest().
	 * @param string                $logical_name e.g. 'native-ui.js'.
	 * @return string Hashed filename, or the logical name as-is.
	 */
	private function get_asset_filename( array $manifest, string $logical_name ): string {
		$value = $manifest[ $logical_name ] ?? null;
		return ( is_string( $value ) && '' !== $value ) ? $value : $logical_name;
	}

	/**
	 * Get the asset version string for cache busting.
	 *
	 * @return string
	 */
	private function get_asset_version(): string {
		if ( defined( 'GDMU_SITE_DESIGNER_VERSION' ) ) {
			return (string) GDMU_SITE_DESIGNER_VERSION;
		}

		return '1.0.0';
	}

	/**
	 * Build the configuration array passed to the React app.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config(): array {
		$website_id = defined( 'GD_ACCOUNT_UID' ) ? (string) GD_ACCOUNT_UID : '';
		$brief_mode = $this->get_brief_mode();
		$action     = $this->get_effective_action();

		return array(
			'apiDomain'            => $this->config->get_api_domain(),
			'apiPathPrefix'        => $this->config->get_api_path_prefix(),
			'wsPathPrefix'         => $this->config->get_ws_path_prefix(),
			'websiteId'            => $website_id,
			'environment'          => $this->config->get_env(),
			'adminUrl'             => admin_url(),
			'restUrl'              => rest_url(),
			'version'              => $this->get_asset_version(),
			'action'               => $action,
			'briefMode'            => $brief_mode,
			'wpRestNonce'          => wp_create_nonce( 'wp_rest' ),
			'activePalette'        => Palette_Switcher::get_active_palette(),
			'activeFontPairing'    => Font_Pairing::get_active_font_pairing(),
			'activeStyleKit'       => Style_Kit::get_active_style_kit(),
			'currentPaletteColors' => self::get_current_palette_colors(),
			'isBlockTheme'         => wp_is_block_theme(),
			'isAdmin'              => is_admin(),
			'siteActivated'        => (bool) get_option( 'wp_site_designer_activated', false ),
			'locale'               => get_user_locale(),
			'bundleBaseUrl'        => $this->get_cdn_base_url(),
			'apmServerUrl'         => $this->config->get_apm_server_url(),
			'isPublished'          => (bool) get_option( 'gdl_site_published', false ),
		);
	}

	/**
	 * Resolve brief mode from query string.
	 *
	 * Expert mode is restricted to wp-admin only — it relies on wp_enqueue_media()
	 * and is not intended for frontend access.
	 *
	 * @return string Sanitized brief mode, currently "expert" or empty string.
	 */
	private function get_brief_mode(): string {
		// Expert mode only works in wp-admin.
		if ( ! is_admin() ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		$brief = isset( $_GET['brief'] ) ? sanitize_text_field( wp_unslash( $_GET['brief'] ) ) : '';

		return 'expert' === $brief ? 'expert' : '';
	}

	/**
	 * Resolve effective action. brief=expert always maps to generate.
	 *
	 * Must stay aligned with the expert-mode URL contract (brief wins over ai-action;
	 * brief=expert alone satisfies the enqueue gate). See monorepo
	 * docs/design/expert-mode-integration-plan.md (Resolved: brief=expert and ai-action).
	 *
	 * Expert mode is restricted to wp-admin only — it relies on wp_enqueue_media()
	 * and is not intended for frontend access.
	 *
	 * @return string Sanitized action value.
	 */
	private function get_effective_action(): string {
		// Expert mode only works in wp-admin (requires wp.media for brand assets).
		if ( is_admin() && 'expert' === $this->get_brief_mode() ) {
			return 'generate';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		return isset( $_GET['ai-action'] ) ? sanitize_text_field( wp_unslash( $_GET['ai-action'] ) ) : '';
	}

	/**
	 * Read the resolved theme.json color palette and return hex values
	 * keyed by our standard slots (base, contrast, accent1–accent5).
	 *
	 * @return array<string, string> Hex colors keyed by slot name, e.g. ['base' => '#FFFFFF', ...].
	 */
	private static function get_current_palette_colors(): array {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}

		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		if ( empty( $palette ) || ! is_array( $palette ) ) {
			return array();
		}

		// Map theme.json slugs to our config keys.
		$slug_map = array(
			'base'     => 'base',
			'contrast' => 'contrast',
			'accent-1' => 'accent1',
			'accent-2' => 'accent2',
			'accent-3' => 'accent3',
			'accent-4' => 'accent4',
			'accent-5' => 'accent5',
		);

		$colors = array();
		foreach ( $palette as $entry ) {
			if ( isset( $entry['slug'], $entry['color'], $slug_map[ $entry['slug'] ] ) ) {
				$colors[ $slug_map[ $entry['slug'] ] ] = $entry['color'];
			}
		}

		return $colors;
	}
}
