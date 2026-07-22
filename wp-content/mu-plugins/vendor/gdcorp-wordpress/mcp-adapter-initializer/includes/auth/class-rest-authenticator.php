<?php
declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\Auth;

/**
 * REST API authentication orchestrator.
 *
 * Iterates registered Auth_Provider instances by priority,
 * delegating credential detection and validation to the first
 * provider that claims the request.
 *
 * @package mcp-adapter-initializer
 * @since 1.6.0
 */
class Rest_Authenticator {

	/**
	 * Singleton instance.
	 *
	 * @var Rest_Authenticator|null
	 */
	private static ?Rest_Authenticator $instance = null;

	/**
	 * Registered authentication providers sorted by priority.
	 *
	 * @var Auth_Provider[]
	 */
	private array $providers = array();

	/**
	 * Cached authentication result for the current request.
	 *
	 * @var array{user_id: int, error: ?string, claimed: bool}|null
	 */
	private ?array $auth_result_cache = null;

	/**
	 * The provider that claimed the current request.
	 *
	 * @var Auth_Provider|null
	 */
	private ?Auth_Provider $active_provider = null;

	/**
	 * Initialize the singleton with the given providers.
	 *
	 * Creates a new instance on first call, returns the existing one thereafter.
	 *
	 * @param Auth_Provider[] $providers Authentication providers.
	 * @return Rest_Authenticator
	 */
	public static function init( array $providers = array() ): Rest_Authenticator {
		if ( null === self::$instance ) {
			self::$instance = new self( $providers );
		}

		return self::$instance;
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return Rest_Authenticator|null Instance, or null if not yet initialized.
	 */
	public static function get_instance(): ?Rest_Authenticator {
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param Auth_Provider[] $providers Authentication providers sorted by priority.
	 */
	public function __construct( array $providers = array() ) {
		$this->register_providers( $providers );
	}

	/**
	 * Register multiple authentication providers at once.
	 *
	 * @param Auth_Provider[] $providers Provider instances.
	 */
	public function register_providers( array $providers ): void {
		foreach ( $providers as $provider ) {
			if ( $provider instanceof Auth_Provider ) {
				$this->providers[] = $provider;
			}
		}

		$this->sort_providers();
	}

	/**
	 * Register a single authentication provider.
	 *
	 * @param Auth_Provider $provider Provider instance to register.
	 */
	public function register_provider( Auth_Provider $provider ): void {
		$this->providers[] = $provider;

		$this->sort_providers();
	}

	/**
	 * Remove all providers of a given class.
	 *
	 * @param string $class_name Fully-qualified class name to remove.
	 */
	public function unregister_provider( string $class_name ): void {
		$this->providers = array_values(
			array_filter(
				$this->providers,
				static function ( Auth_Provider $provider ) use ( $class_name ): bool {
					return ! ( $provider instanceof $class_name );
				}
			)
		);
	}

	/**
	 * Sort providers by priority (lower = checked first).
	 */
	private function sort_providers(): void {
		usort(
			$this->providers,
			static function ( Auth_Provider $a, Auth_Provider $b ): int {
				return $a->get_priority() <=> $b->get_priority();
			}
		);
	}

	/**
	 * WordPress filter callback for `determine_current_user`.
	 *
	 * Iterates providers by priority. The first provider whose
	 * can_handle() returns true wins exclusively — no further
	 * providers are consulted, even on authentication failure.
	 *
	 * @param mixed $user_id Current user ID from previous filters.
	 * @return int Resolved user ID.
	 */
	public function determine_current_user( $user_id ): int {
		$user_id = (int) $user_id;

		if ( $user_id > 0 ) {
			return $user_id;
		}

		if ( null !== $this->auth_result_cache ) {
			return $this->auth_result_cache['user_id'];
		}

		foreach ( $this->providers as $provider ) {
			if ( ! $provider->can_handle() ) {
				continue;
			}

			$authenticated_id = $provider->authenticate();

			if ( $authenticated_id > 0 ) {
				$this->active_provider   = $provider;
				$this->auth_result_cache = array(
					'user_id' => $authenticated_id,
					'error'   => null,
					'claimed' => true,
				);
				return $authenticated_id;
			}

			$this->auth_result_cache = array(
				'user_id' => 0,
				'error'   => $provider->get_error(),
				'claimed' => true,
			);
			return 0;
		}

		return $user_id;
	}

	/**
	 * WordPress filter callback for `rest_authentication_errors`.
	 *
	 * Translates cached authentication results into REST API errors
	 * or clears errors for authenticated requests.
	 *
	 * WordPress core normally calls wp_get_current_user() (which triggers
	 * determine_current_user) before rest_authentication_errors, but we
	 * guard against unusual call ordering by running provider evaluation
	 * here if the cache is still empty.
	 *
	 * @param mixed $errors Existing authentication errors.
	 * @return \WP_Error|null|mixed WP_Error on failure, null on success, passthrough otherwise.
	 */
	public function check_authentication_errors( $errors ) {
		if ( is_wp_error( $errors ) ) {
			return $errors;
		}

		if ( null === $this->auth_result_cache ) {
			$this->determine_current_user( 0 );
		}

		if ( null === $this->auth_result_cache || ! $this->auth_result_cache['claimed'] ) {
			return $errors;
		}

		if ( $this->auth_result_cache['user_id'] > 0 ) {
			return null;
		}

		return new \WP_Error(
			'rest_forbidden',
			$this->auth_result_cache['error'] ?? 'Authentication failed.',
			array( 'status' => 401 )
		);
	}

	/**
	 * Retrieve the token payload from the active provider.
	 *
	 * Uses duck-typing: delegates to the active provider's
	 * get_token_payload() method if it exists.
	 *
	 * @return array|null Token payload array, or null if unavailable.
	 */
	public function get_token_payload(): ?array {
		if ( null === $this->active_provider ) {
			return null;
		}

		if ( method_exists( $this->active_provider, 'get_token_payload' ) ) {
			return $this->active_provider->get_token_payload();
		}

		return null;
	}
}
