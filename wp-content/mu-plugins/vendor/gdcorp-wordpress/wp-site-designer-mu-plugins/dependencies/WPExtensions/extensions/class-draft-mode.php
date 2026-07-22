<?php
/**
 * Draft Mode Class
 *
 * Provides WordPress admin UI support for draft page management:
 * draft context injection for the Native UI banner, page list column, and settings.
 *
 * All CRUD operations (create, publish, discard) live in mcp-adapter-initializer
 * (Draft_Page_Helper). Banner rendering lives in packages/native-ui (DraftBanner).
 * This class injects the draft context data and handles page list UI.
 *
 * @package GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Draft Mode Class - WordPress admin UI for draft pages
 */
class Draft_Mode {

	public const META_DRAFT_OF      = '_sd_draft_of';
	public const META_HAS_DRAFT     = '_sd_has_draft';
	public const META_DRAFT_CREATED = '_sd_draft_created';

	/**
	 * Constructor
	 */
	public function __construct() {
		$settings = $this->get_settings();
		if ( empty( $settings['enable'] ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'inject_draft_context' ), 20 );

		if ( ! empty( $settings['show_page_list_column'] ) ) {
			add_filter( 'manage_pages_columns', array( $this, 'add_draft_status_column' ) );
			add_action( 'manage_pages_custom_column', array( $this, 'render_draft_status_column' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_column_styles' ) );
		}
	}

	/**
	 * Initialize the class and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;
		new self();
	}

	/**
	 * Get draft mode settings.
	 *
	 * @return array<string, mixed> Draft mode settings.
	 */
	public function get_settings(): array {
		$defaults = array(
			'enable'                => true,
			'show_page_list_column' => true,
		);

		/**
		 * Filters the draft mode settings.
		 *
		 * @param array $defaults Default settings.
		 */
		return apply_filters( 'wp_site_designer_draft_mode_settings', $defaults );
	}

	/**
	 * Check if draft mode is enabled.
	 *
	 * @return bool True if enabled.
	 */
	public function is_enabled(): bool {
		$settings = $this->get_settings();
		return ! empty( $settings['enable'] );
	}

	/**
	 * Untrash a Site Designer draft, force 'draft' status, and re-link it to its
	 * original page. Mirrors Draft_Page_Helper::untrash_post() in
	 * mcp-adapter-initializer — keep the two in sync. The caller restores the
	 * draft's revision afterwards.
	 *
	 * @param int $draft_id The trashed draft post ID.
	 * @return int|\WP_Error The re-linked original page ID on success, WP_Error on failure.
	 */
	public static function untrash_draft( int $draft_id ) {
		$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );

		$original = get_post( $original_id );
		if ( ! $original ) {
			return new \WP_Error(
				'invalid_original',
				__( 'The original page no longer exists.', 'wp-site-designer-wp-extensions' ),
				array( 'status' => 404 )
			);
		}

		// Guard against clobbering a different active draft created after this one was trashed.
		$existing_draft_id = (int) get_post_meta( $original_id, self::META_HAS_DRAFT, true );
		if ( $existing_draft_id && $existing_draft_id !== $draft_id ) {
			$existing_draft = get_post( $existing_draft_id );
			if ( $existing_draft && 'draft' === $existing_draft->post_status ) {
				return new \WP_Error(
					'draft_exists',
					__( 'The original page already has an active draft.', 'wp-site-designer-wp-extensions' ),
					array( 'status' => 409 )
				);
			}
		}

		$untrashed = wp_untrash_post( $draft_id );
		if ( ! $untrashed ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for failed wp_untrash_post.
			error_log( 'Draft_Mode: wp_untrash_post failed for draft ' . $draft_id );
			return new \WP_Error(
				'untrash_failed',
				__( 'Failed to restore the draft from trash.', 'wp-site-designer-wp-extensions' ),
				array( 'status' => 500 )
			);
		}

		// wp_untrash_post() restores the pre-trash status, which is 'draft' for a
		// Site Designer draft. Anything else means the invariant was broken upstream
		// (manual status change or another plugin); coerce it back defensively.
		$current = get_post( $draft_id );
		if ( $current && 'draft' !== $current->post_status ) {
			$status_updated = wp_update_post(
				array(
					'ID'          => $draft_id,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $status_updated ) || 0 === $status_updated ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for failed status update.
				error_log( 'Draft_Mode: failed to force draft status for draft ' . $draft_id );
				return new \WP_Error(
					'status_update_failed',
					__( 'Failed to force the restored draft back to draft status.', 'wp-site-designer-wp-extensions' ),
					array( 'status' => 500 )
				);
			}
		}

		update_post_meta( $original_id, self::META_HAS_DRAFT, $draft_id );

		// update_post_meta() returns false on unchanged values too, so read back
		// to confirm the re-link (fail-closed).
		if ( (int) get_post_meta( $original_id, self::META_HAS_DRAFT, true ) !== $draft_id ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional error logging for failed draft re-link.
			error_log( 'Draft_Mode: failed to re-link draft ' . $draft_id . ' to original page ' . $original_id );
			return new \WP_Error(
				'relink_failed',
				__( 'The draft was restored from trash but could not be re-linked to its original page.', 'wp-site-designer-wp-extensions' ),
				array( 'status' => 500 )
			);
		}

		return $original_id;
	}

	/**
	 * Inject draft context for the Native UI React banner.
	 *
	 * Sets window.sdDraftContext as an inline script on the Native UI handle
	 * so the React DraftBanner component can pick it up. The banner rendering
	 * itself lives in packages/native-ui.
	 *
	 * @return void
	 */
	public function inject_draft_context(): void {
		global $post;
		if ( ! $post || 'draft' !== $post->post_status ) {
			return;
		}

		if ( ! wp_script_is( 'site-designer-native-ui', 'registered' ) ) {
			return;
		}

		$original_id = get_post_meta( $post->ID, self::META_DRAFT_OF, true );
		if ( ! $original_id ) {
			return;
		}

		$original = get_post( (int) $original_id );
		if ( ! $original || 'page' !== $original->post_type ) {
			return;
		}
		$original_title = $original->post_title;

		$context = wp_json_encode(
			array(
				'draftId'       => $post->ID,
				'originalTitle' => $original_title,
				'pagesUrl'      => admin_url( 'edit.php?post_type=page' ),
			)
		);

		if ( ! $context ) {
			return;
		}

		wp_add_inline_script(
			'site-designer-native-ui',
			'window.sdDraftContext = ' . $context . ';',
			'before'
		);
	}

	/**
	 * Add the "Airo Draft" column to the Pages list table.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function add_draft_status_column( array $columns ): array {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['sd_airo_draft_status'] = __( 'Airo Draft', 'wp-site-designer-wp-extensions' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render the "Airo Draft" column value for each page row.
	 *
	 * @param string $column_name Current column name.
	 * @param int    $post_id     Current post ID.
	 * @return void
	 */
	public function render_draft_status_column( string $column_name, int $post_id ): void {
		if ( 'sd_airo_draft_status' !== $column_name ) {
			return;
		}

		$post        = get_post( $post_id );
		$original_id = get_post_meta( $post_id, self::META_DRAFT_OF, true );

		if ( $original_id ) {
			$original = get_post( (int) $original_id );
			$title    = $original
				? $original->post_title
				: '#' . intval( $original_id ) . ' (missing)';

			printf(
				'<span class="sd-airo-draft-badge sd-airo-draft-badge--is-draft" title="%s">%s</span>',
				esc_attr(
					sprintf(
						/* translators: %s is the original page title. */
						__( 'Airo draft of: %s', 'wp-site-designer-wp-extensions' ),
						$title
					)
				),
				esc_html__( 'Draft Version', 'wp-site-designer-wp-extensions' )
			);
			return;
		}

		if ( $post && 'publish' === $post->post_status ) {
			$draft_id = get_post_meta( $post_id, self::META_HAS_DRAFT, true );
			if ( $draft_id ) {
				$draft = get_post( $draft_id );
				if ( $draft && 'draft' === $draft->post_status ) {
					$created = get_post_meta( $draft->ID, self::META_DRAFT_CREATED, true );
					printf(
						'<span class="sd-airo-draft-badge sd-airo-draft-badge--has-draft" title="%s">%s</span>',
						esc_attr(
							sprintf(
								/* translators: %s is the date the Airo draft was created. */
								__( 'Airo draft created: %s', 'wp-site-designer-wp-extensions' ),
								$created
							)
						),
						esc_html__( 'Has Draft', 'wp-site-designer-wp-extensions' )
					);
					return;
				}
			}
		}

		echo '<span class="sd-airo-draft-badge sd-airo-draft-badge--none">&mdash;</span>';
	}

	/**
	 * Enqueue inline styles for the Airo Draft column on the Pages list screen.
	 *
	 * @param string|null $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_column_styles( ?string $hook_suffix = null ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}

		wp_add_inline_style(
			'common',
			'
			.sd-airo-draft-badge {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 12px;
				line-height: 1.4;
			}
			.sd-airo-draft-badge--is-draft {
				background: #ede9fb;
				color: #4a2f8a;
			}
			.sd-airo-draft-badge--has-draft {
				background: #744bc4;
				color: #fff;
			}
			.sd-airo-draft-badge--none {
				color: #999;
			}
			'
		);
	}
}
