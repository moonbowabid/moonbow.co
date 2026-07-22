<?php
/**
 * Update Site Options Tool Class
 *
 * @package     mcp-adapter-initializer
 * @author      GoDaddy
 * @copyright   2025 GoDaddy
 * @license     GPL-2.0-or-later
 */

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Update Site Options Tool
 *
 * Handles the registration and execution of the update site options ability
 * for the MCP adapter.
 */
class Update_Site_Options_Tool extends Base_Tool {
	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/update-site-options';

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
		* @return self
		*/
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Register the update site options ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Update Site Options', 'mcp-adapter-initializer' ),
				'description'         => __( 'Updates WordPress site options. Can update a single option or multiple options in one call', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'site-management',
			)
		);
	}

	/**
	 * Get the tool identifier
	 *
	 * @return string
	 */
	public function get_tool_id(): string {
		return self::TOOL_ID;
	}

	/**
	 * Get input schema for the tool
	 *
	 * @return array
	 */
	private function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'option_name'  => array(
					'type'        => 'string',
					'description' => __( 'The name of the option to update (for single option updates)', 'mcp-adapter-initializer' ),
				),
				'option_value' => array(
					'type'        => array( 'string', 'number', 'boolean', 'array', 'object', 'null' ),
					'description' => __( 'The value for the option (for single option updates)', 'mcp-adapter-initializer' ),
				),
				'options'      => array(
					'type'        => 'array',
					'description' => __( 'Array of options to update in bulk. Each item should have option_name and option_value', 'mcp-adapter-initializer' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'option_name'  => array(
								'type'        => 'string',
								'description' => __( 'The name of the option', 'mcp-adapter-initializer' ),
							),
							'option_value' => array(
								'type'        => array( 'string', 'number', 'boolean', 'array', 'object', 'null' ),
								'description' => __( 'The value for the option', 'mcp-adapter-initializer' ),
							),
						),
						'required'   => array( 'option_name', 'option_value' ),
					),
				),
			),
			'anyOf'      => array(
				array(
					'required' => array( 'option_name', 'option_value' ),
				),
				array(
					'required' => array( 'options' ),
				),
			),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Site options update result', 'mcp-adapter-initializer' ),
			array(
				'updated_count' => array(
					'type'        => 'integer',
					'description' => __( 'Number of options successfully updated', 'mcp-adapter-initializer' ),
				),
				'failed_count'  => array(
					'type'        => 'integer',
					'description' => __( 'Number of options that failed to update', 'mcp-adapter-initializer' ),
				),
				'results'       => array(
					'type'        => 'array',
					'description' => __( 'Detailed results for each option update', 'mcp-adapter-initializer' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'option_name' => array(
								'type'        => 'string',
								'description' => __( 'The option name', 'mcp-adapter-initializer' ),
							),
							'success'     => array(
								'type'        => 'boolean',
								'description' => __( 'Whether this specific option update was successful', 'mcp-adapter-initializer' ),
							),
							'message'     => array(
								'type'        => 'string',
								'description' => __( 'Success or error message for this option', 'mcp-adapter-initializer' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Execute the update site options tool
	 *
	 * @param array $input Input parameters
	 * @return array Update result or error
	 */
	public function execute( array $input ): array {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to manage site options', 'mcp-adapter-initializer' ),
			);
		}

		$has_single = isset( $input['option_name'] ) && array_key_exists( 'option_value', $input );
		$has_bulk   = isset( $input['options'] ) && is_array( $input['options'] );

		if ( $has_single && $has_bulk ) {
			return array(
				'success' => false,
				'message' => __( 'Provide either a single option (option_name + option_value) or bulk options, not both', 'mcp-adapter-initializer' ),
			);
		}

		$options_to_update = array();

		if ( $has_single ) {
			$options_to_update[] = array(
				'option_name'  => $input['option_name'],
				'option_value' => $input['option_value'],
			);
		} elseif ( $has_bulk ) {
			foreach ( $input['options'] as $option ) {
				if ( isset( $option['option_name'] ) && array_key_exists( 'option_value', $option ) ) {
					$options_to_update[] = array(
						'option_name'  => $option['option_name'],
						'option_value' => $option['option_value'],
					);
				}
			}
		}

		// Validate that we have options to update
		if ( empty( $options_to_update ) ) {
			return array(
				'success' => false,
				'message' => __( 'No valid options provided to update', 'mcp-adapter-initializer' ),
			);
		}

		// Pre-validate entire request before any update_option(): fail closed (no partial writes).
		// Order is intentional: canonicalize name → reject empty → reject protected options → then build
		// the batch. WPLANG value normalization never runs until the apply loop below; protection
		// is never "after" normalization in a way that could confuse auditors—blocked names exit here.
		$normalized_batch = array();
		foreach ( $options_to_update as $option_data ) {
			if ( ! isset( $option_data['option_name'] ) || ! is_string( $option_data['option_name'] ) ) {
				return array(
					'success' => false,
					'message' => __( 'Each option must have a string option_name', 'mcp-adapter-initializer' ),
				);
			}

			$option_name = $this->canonical_option_name( sanitize_text_field( $option_data['option_name'] ) );

			if ( '' === $option_name ) {
				return array(
					'success' => false,
					'message' => __( 'Option name cannot be empty', 'mcp-adapter-initializer' ),
				);
			}

			if ( $this->is_protected_option( $option_name ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: option name */
						__( 'Request rejected: option "%s" is protected and cannot be modified. No options were updated.', 'mcp-adapter-initializer' ),
						$option_name
					),
				);
			}

			$normalized_batch[] = array(
				'option_name'  => $option_name,
				'option_value' => $option_data['option_value'],
			);
		}

		$results       = array();
		$updated_count = 0;
		$failed_count  = 0;

		foreach ( $normalized_batch as $option_data ) {
			$option_name  = $option_data['option_name'];
			$option_value = $option_data['option_value'];

			// Names here already passed is_protected_option() in pre-validation above.
			// Normalize values only for allowed options (WPLANG), then persist.
			$option_value_to_update = $option_value;
			if ( 'WPLANG' === $option_name ) {
				// Ensure WPLANG is only updated with string or null values to avoid corrupting the site language.
				if ( null !== $option_value && ! is_string( $option_value ) ) {
					$results[] = array(
						'option_name' => $option_name,
						'success'     => false,
						'message'     => __( 'Invalid value for WPLANG option; expected string or null.', 'mcp-adapter-initializer' ),
					);
					++$failed_count;
					continue;
				}
				$option_value_to_update = $this->normalize_wplang_value( $option_value );

				// Install core translations before persisting WPLANG.
				// NOTE: Language pack install happens in-loop. If it fails, earlier options are already updated (partial state).
				// Callers should update WPLANG alone (site-designer-api does this) or process it first.
				if ( '' !== $option_value_to_update ) {
					$current_wplang = $this->normalize_wplang_value( get_option( 'WPLANG' ) );
					if ( $current_wplang !== $option_value_to_update ) {
						$pack_result = $this->ensure_core_language_pack_for_locale( $option_value_to_update );
						if ( ! $pack_result['success'] ) {
							$results[] = array(
								'option_name' => $option_name,
								'success'     => false,
								'message'     => $pack_result['message'],
							);
							++$failed_count;
							continue;
						}
					}
				}
			}

			$update_result = update_option( $option_name, $option_value_to_update );

			if ( $update_result ) {
				$results[] = array(
					'option_name' => $option_name,
					'success'     => true,
					'message'     => sprintf( __( 'Option "%s" updated successfully', 'mcp-adapter-initializer' ), $option_name ),
				);

				++$updated_count;
			} else {
				// update_option returns false if the value is the same or if it failed
				// Check if the current value is the same as what we're trying to set
				$current_value = get_option( $option_name );
				// Normalize boolean to WordPress storage format ('1'/'0') for comparison
				// since WordPress stores booleans as strings in wp_options
				$compare_value = $option_value;
				if ( is_bool( $option_value ) ) {
					$compare_value = $option_value ? '1' : '0';
				}
				$current_value_for_compare = $current_value;
				if ( 'WPLANG' === $option_name ) {
					$current_value_for_compare = $this->normalize_wplang_value( $current_value );
					$compare_value             = $this->normalize_wplang_value( $option_value_to_update );
				}
				if ( $current_value_for_compare === $compare_value ) {
					$results[] = array(
						'option_name' => $option_name,
						'success'     => true,
						'message'     => sprintf( __( 'Option "%s" already has the same value', 'mcp-adapter-initializer' ), $option_name ),
					);

					++$updated_count;
				} else {
					$results[] = array(
						'option_name' => $option_name,
						'success'     => false,
						'message'     => sprintf( __( 'Failed to update option "%s"', 'mcp-adapter-initializer' ), $option_name ),
					);

					++$failed_count;
				}
			}
		}

		// Generate overall message
		if ( 0 === $failed_count ) {
			$message = sprintf( __( 'Successfully updated %d option(s)', 'mcp-adapter-initializer' ), $updated_count );
		} elseif ( 0 === $updated_count ) {
			$message = sprintf( __( 'Failed to update all %d option(s)', 'mcp-adapter-initializer' ), $failed_count );
		} else {
			$message = sprintf( __( 'Updated %1$d option(s), failed to update %2$d option(s)', 'mcp-adapter-initializer' ), $updated_count, $failed_count );
		}

		return array(
			'success'       => 0 === $failed_count,
			'updated_count' => $updated_count,
			'failed_count'  => $failed_count,
			'results'       => $results,
			'message'       => $message,
		);
	}

	/**
	 * Map user-supplied option keys to WordPress-canonical names where needed.
	 *
	 * Prevents duplicate rows (e.g. `wplang` vs `WPLANG`) and keeps WPLANG handling on the real option.
	 *
	 * @param string $option_name Sanitized option name.
	 * @return string Canonical name for storage.
	 */
	private function canonical_option_name( string $option_name ): string {
		if ( 0 === strcasecmp( $option_name, 'WPLANG' ) ) {
			return 'WPLANG';
		}
		return $option_name;
	}

	/**
	 * Check if an option is protected and shouldn't be modified
	 *
	 * Comparison is case-insensitive so variants like `Auth_Key` cannot bypass the blocklist.
	 *
	 * @param string $option_name The option name to check
	 * @return bool True if the option is protected
	 */
	private function is_protected_option( string $option_name ): bool {
		static $blocked = array(
			'db_version',
			'wp_db_version',
			'secret_key',
			'auth_key',
			'secure_auth_key',
			'logged_in_key',
			'nonce_key',
			'auth_salt',
			'secure_auth_salt',
			'logged_in_salt',
			'nonce_salt',
		);

		$lower = strtolower( $option_name );
		foreach ( $blocked as $b ) {
			if ( strtolower( $b ) === $lower ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Normalize WPLANG values for updates and comparisons.
	 *
	 * Required (not redundant with the API): MCP callers may send any JSON shape (`es-ES`, `en_US`, null).
	 * WordPress often stores default English as `''`; we fold `en_US` / `en-US` to `''` so
	 * `get_option` / `update_option` / “already same value?” checks stay consistent.
	 *
	 * @param mixed $value Raw WPLANG value.
	 * @return string Normalized locale value (empty string means default English).
	 */
	private function normalize_wplang_value( $value ): string {
		if ( null === $value ) {
			return '';
		}

		$normalized = trim( (string) $value );
		if ( '' === $normalized ) {
			return '';
		}

		$normalized = str_replace( '-', '_', $normalized );
		return 'en_US' === $normalized ? '' : $normalized;
	}

	/**
	 * @param string $locale Normalized WPLANG (e.g. es_ES).
	 * @return array{success:bool, message:string}
	 */
	private function ensure_core_language_pack_for_locale( string $locale ): array {
		$this->load_admin_file( 'file.php' );
		$this->load_admin_file( 'translation-install.php' );

		if ( ! function_exists( 'wp_download_language_pack' ) ) {
			return array(
				'success' => false,
				'message' => __( 'WordPress language install API is not available', 'mcp-adapter-initializer' ),
			);
		}

		$result = wp_download_language_pack( $locale );

		if ( false === $result ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: WordPress locale */
					__( 'Could not download or install core language pack for %s', 'mcp-adapter-initializer' ),
					$locale
				),
			);
		}

		return array(
			'success' => true,
			'message' => '',
		);
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
	/**
	 * Prevent unserialization
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
