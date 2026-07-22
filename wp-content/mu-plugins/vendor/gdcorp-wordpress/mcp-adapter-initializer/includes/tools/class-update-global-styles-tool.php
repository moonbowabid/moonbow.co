<?php
/**
 * Update Global Styles Tool Class
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
 * Update Global Styles Tool
 *
 * Handles the registration and execution of the update global styles ability
 * for the MCP adapter.
 */
class Update_Global_Styles_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/update-global-styles';

	/**
	 * Tool instance
	 *
	 * @var Update_Global_Styles_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Update_Global_Styles_Tool
	 */
	public static function get_instance(): Update_Global_Styles_Tool {
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
	 * Register the update global styles ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'Update Global Styles', 'mcp-adapter-initializer' ),
				'description'         => __( 'Updates global styles configuration including styles, settings, and title', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'theme-management',
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
				'id'        => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the global styles template to update', 'mcp-adapter-initializer' ),
				),
				'styles'    => array(
					'type'        => 'object',
					'description' => __( 'Global styles configuration object', 'mcp-adapter-initializer' ),
				),
				'settings'  => array(
					'type'        => 'object',
					'description' => __( 'Global settings configuration object', 'mcp-adapter-initializer' ),
				),
				'title'     => array(
					'type'        => 'string',
					'description' => __( 'Title of the global styles variation', 'mcp-adapter-initializer' ),
				),
				'overwrite' => array(
					'type'        => 'boolean',
					'description' => __( 'If true, replace entire styles/settings instead of merging. Defaults to false.', 'mcp-adapter-initializer' ),
				),
			),
			'required'   => array( 'id' ),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return $this->build_output_schema(
			__( 'Updated global styles data', 'mcp-adapter-initializer' ),
			array(
				'data' => array(
					'type'        => 'object',
					'description' => __( 'Updated global styles data', 'mcp-adapter-initializer' ),
					'properties'  => array(
						'id'      => array(
							'type'        => 'integer',
							'description' => __( 'The global styles post ID', 'mcp-adapter-initializer' ),
						),
						'title'   => array(
							'type'        => 'string',
							'description' => __( 'The global styles title', 'mcp-adapter-initializer' ),
						),
						'content' => array(
							'type'        => 'object',
							'description' => __( 'The updated global styles configuration', 'mcp-adapter-initializer' ),
						),
						'status'  => array(
							'type'        => 'string',
							'description' => __( 'The post status', 'mcp-adapter-initializer' ),
						),
						'date'    => array(
							'type'        => 'string',
							'description' => __( 'The last modified date', 'mcp-adapter-initializer' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Execute the tool
	 *
	 * @param array $input Tool input parameters.
	 * @return array
	 */
	public function execute( array $input ): array {
		try {
			// Validate required parameters
			$global_styles_id = isset( $input['id'] ) ? absint( $input['id'] ) : 0;

			if ( empty( $global_styles_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'Global styles ID is required', 'mcp-adapter-initializer' ),
				);
			}

			// Get the existing global styles post
			$global_styles_post = get_post( $global_styles_id );

			if ( ! $global_styles_post || 'wp_global_styles' !== $global_styles_post->post_type ) {
				return array(
					'success' => false,
					'message' => __( 'Global styles post not found or invalid ID', 'mcp-adapter-initializer' ),
				);
			}

			// Get existing content and merge with new data
			$existing_content = array();
			if ( ! empty( $global_styles_post->post_content ) ) {
				$decoded = json_decode( $global_styles_post->post_content, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
					$existing_content = $decoded;
				} else {
					// Log the error but continue with empty array (effectively overwrites corrupted data)
					error_log(
						sprintf(
							'Global styles post %d has corrupted JSON content. Starting fresh. Error: %s',
							$global_styles_id,
							json_last_error_msg()
						)
					);
				}
			}
			// Prepare the new content
			$new_content = $existing_content;
			$overwrite   = isset( $input['overwrite'] ) && true === $input['overwrite'];

			// Update styles if provided
			if ( isset( $input['styles'] ) && is_array( $input['styles'] ) ) {
				if ( $overwrite ) {
					// Replace entire styles section
					$new_content['styles'] = $input['styles'];
				} else {
					// Merge with existing styles
					if ( ! isset( $new_content['styles'] ) ) {
						$new_content['styles'] = array();
					}
					$new_content['styles'] = $this->deep_merge_arrays( $new_content['styles'], $input['styles'] );
				}
			}

			// Update settings if provided
			if ( isset( $input['settings'] ) && is_array( $input['settings'] ) ) {
				if ( $overwrite ) {
					// Replace entire settings section
					$new_content['settings'] = $input['settings'];
				} else {
					// Merge with existing settings
					if ( ! isset( $new_content['settings'] ) ) {
						$new_content['settings'] = array();
					}
					$new_content['settings'] = $this->deep_merge_arrays( $new_content['settings'], $input['settings'] );
				}

				// Download Google Fonts if font families are being updated
				if ( isset( $new_content['settings']['typography']['fontFamilies'] ) ) {
					$font_downloader = new Font_Downloader();

					$new_content['settings']['typography']['fontFamilies'] = $font_downloader->process_font_families(
						$new_content['settings']['typography']['fontFamilies']
					);
				}
			}

			// Encode the merged content first so encoding failures (invalid
			// UTF-8, max depth, etc.) surface as a structured error instead of
			// silently coercing `false` into an empty post_content string,
			// which wp_update_post() would happily store — clobbering the
			// entire wp_global_styles post.
			$encoded_content = wp_json_encode( $new_content );
			if ( false === $encoded_content ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: JSON encoding error message. */
						__( 'Failed to encode global styles: %s', 'mcp-adapter-initializer' ),
						json_last_error_msg()
					),
				);
			}

			// Prepare post data for update.
			$post_data = array(
				'ID'           => $global_styles_id,
				'post_content' => $encoded_content,
			);

			// Update title if provided
			if ( isset( $input['title'] ) && ! empty( $input['title'] ) ) {
				$post_data['post_title'] = sanitize_text_field( $input['title'] );
			}

			// wp_update_post() runs wp_unslash() on every string in $post_data,
			// not just post_content. Without wp_slash() on the full array,
			// backslashes in post_content (JSON escape sequences like `\"`,
			// CSS unicode escapes like `\2715`, `\\` literals) and any future
			// backslash-bearing field (post_title, etc.) would be stripped
			// one slash deep — producing invalid JSON that fails
			// WP_Theme_JSON_Resolver::get_user_data() on render. Slashing the
			// whole array once cancels the unslash exactly, so every field
			// round-trips intact regardless of what gets added later.
			$post_data = wp_slash( $post_data );

			// Pass $wp_error = true so genuine update failures surface as a
			// WP_Error with an actionable message instead of a bare 0 that
			// hits the generic fallback below. The ! $update_result branch
			// stays as a defense-in-depth guard for the (now unreachable in
			// practice) zero-return case.
			$update_result = wp_update_post( $post_data, true );

			if ( is_wp_error( $update_result ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						__( 'Failed to update global styles: %s', 'mcp-adapter-initializer' ),
						$update_result->get_error_message()
					),
				);
			}

			if ( ! $update_result ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to update global styles', 'mcp-adapter-initializer' ),
				);
			}

			// Build the response entirely from in-memory values — no get_post()
			// read-back. A persistent object cache (Redis) is not guaranteed to
			// reflect the write immediately: clean_post_cache() only flushes the
			// local WP cache, not external layers, and race conditions can
			// re-populate a stale entry before the next read. Using in-memory
			// values is both simpler and strictly correct:
			//   id     — wp_update_post() return value (the post ID).
			//   title  — the sanitized value we just wrote, or the original.
			//   content — $new_content, exactly what was encoded and stored.
			//   status  — unchanged; this tool never sets post_status.
			//   date    — current_time() mirrors what wp_update_post() stores.
			return array(
				'success' => true,
				'data'    => array(
					'id'      => (int) $update_result,
					'title'   => isset( $input['title'] ) && ! empty( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $global_styles_post->post_title,
					'content' => $new_content,
					'status'  => $global_styles_post->post_status,
					'date'    => current_time( 'mysql' ),
				),
				'message' => __( 'Global styles updated successfully', 'mcp-adapter-initializer' ),
			);

		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf(
					__( 'Error updating global styles: %s', 'mcp-adapter-initializer' ),
					$e->getMessage()
				),
			);
		}
	}

	/**
	 * Deep merge two arrays recursively with intelligent array handling
	 *
	 * @param array $existing The existing array.
	 * @param array $new_data The new array to merge.
	 * @return array
	 */
	private function deep_merge_arrays( $existing, $new_data ) {
		foreach ( $new_data as $key => $value ) {
			if ( is_array( $value ) && isset( $existing[ $key ] ) && is_array( $existing[ $key ] ) ) {
				// Lists of preset objects (e.g., color.palette, typography.fontFamilies,
				// typography.fontSizes, spacing.spacingSizes, shadow.presets) must be
				// replaced wholesale — never merged by index. Origin-keyed shapes like
				// {"theme": [...], "default": [...]} also appear at these paths because
				// WordPress' wp_theme_json_data_user filter wraps user data when it
				// exposes the merged structure. Merging a flat list against an
				// origin-keyed dict — in either direction — produces malformed
				// {"0":..., "1":..., "theme":[...]} output that crashes WP's preset
				// iterator (class-wp-theme-json.php "Undefined array key 'slug'"
				// warnings). Treat list-shape on either side as the signal to replace.
				if ( $this->is_list_of_objects( $value ) || $this->is_list_of_objects( $existing[ $key ] ) ) {
					// Replace the entire value with the new data. We prefer the new
					// payload as the winner because callers explicitly opt into a
					// merge by omitting `overwrite=true`; if they wanted to preserve
					// existing presets at this key, they would have omitted it from
					// the request.
					$existing[ $key ] = $value;
				} else {
					// Both sides are associative — safe to deep-merge (e.g.,
					// settings.custom.*, styles.blocks.<name>.*).
					$existing[ $key ] = $this->deep_merge_arrays( $existing[ $key ], $value );
				}
			} else {
				$existing[ $key ] = $value;
			}
		}
		return $existing;
	}

	/**
	 * Check if an array is a numeric-indexed list of objects/arrays
	 *
	 * This identifies arrays like:
	 * - Font families: [{ name: "Oswald", ... }, { name: "Quattrocento", ... }]
	 * - Color palettes: [{ color: "#FFF", ... }, { color: "#000", ... }]
	 *
	 * These should be replaced entirely, not merged by index.
	 *
	 * @param array $value The array to check.
	 * @return bool True if this is a list of objects that should be replaced.
	 */
	private function is_list_of_objects( $value ) {
		// Empty arrays are not lists of objects
		if ( empty( $value ) ) {
			return false;
		}

		// Check if array has sequential numeric keys starting from 0
		$keys = array_keys( $value );
		if ( range( 0, count( $value ) - 1 ) !== $keys ) {
			// Not a numeric-indexed array (it's associative)
			return false;
		}

		// Check if the first element is an array/object
		// If the first element is an array, assume the whole array is a list of objects
		return is_array( $value[0] );
	}
}
