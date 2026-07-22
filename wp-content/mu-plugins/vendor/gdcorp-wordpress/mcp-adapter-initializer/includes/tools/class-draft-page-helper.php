<?php
/**
 * Draft Page Helper
 *
 * Shared draft operations (publish, discard, lookup, cleanup, REST endpoints).
 * Draft creation lives in Create_Page_Draft_Tool.
 * Uses only WordPress core functions — no dependency on wp-site-designer-mu-plugins.
 *
 * @package     mcp-adapter-initializer
 * @author      GoDaddy
 * @copyright   2025 GoDaddy
 * @license     GPL-2.0-or-later
 */

namespace GoDaddy\WordPress\Plugins\MCPAdapterInitializer\MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Draft Page Helper — shared draft operations (publish, discard, lookup, cleanup, REST).
 */
class Draft_Page_Helper {

	const META_DRAFT_OF      = '_sd_draft_of';
	const META_HAS_DRAFT     = '_sd_has_draft';
	const META_DRAFT_CREATED = '_sd_draft_created';

	/**
	 * Get the draft for a published post.
	 *
	 * @param int $post_id The published post ID.
	 * @return \WP_Post|null Draft post or null.
	 */
	public static function get_draft( int $post_id ): ?\WP_Post {
		$draft_id = get_post_meta( $post_id, self::META_HAS_DRAFT, true );

		if ( ! $draft_id ) {
			return null;
		}

		$draft = get_post( $draft_id );

		if ( ! $draft || 'draft' !== $draft->post_status ) {
			return null;
		}

		// Verify the draft actually belongs to this post (guards against stale meta)
		if ( (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true ) !== $post_id ) {
			return null;
		}

		return $draft;
	}

	/**
	 * Check if a post has an active draft.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if draft exists.
	 */
	public static function has_draft( int $post_id ): bool {
		return null !== self::get_draft( $post_id );
	}

	/**
	 * Publish a draft by merging its content into the original page, then remove the draft.
	 *
	 * @param int  $draft_id     The draft post ID.
	 * @param bool $force_delete When false (default), the draft is trashed so it can be restored;
	 *                           when true, it is permanently deleted.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function publish_draft( int $draft_id, bool $force_delete = false ) {
		$draft = get_post( $draft_id );

		if ( ! $draft ) {
			return new \WP_Error(
				'invalid_post',
				__( 'Post not found.', 'mcp-adapter-initializer' ),
				array( 'status' => 404 )
			);
		}

		if ( 'page' !== $draft->post_type ) {
			return new \WP_Error(
				'invalid_post_type',
				__( 'Draft mode is only available for pages.', 'mcp-adapter-initializer' ),
				array( 'status' => 400 )
			);
		}

		if ( 'draft' !== $draft->post_status ) {
			return new \WP_Error(
				'invalid_status',
				__( 'Only draft pages can be published from draft mode.', 'mcp-adapter-initializer' ),
				array( 'status' => 400 )
			);
		}

		$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );
		if ( ! $original_id ) {
			return new \WP_Error(
				'missing_original',
				__( 'This draft is not linked to a published page.', 'mcp-adapter-initializer' ),
				array( 'status' => 400 )
			);
		}

		$original = get_post( $original_id );
		if ( ! $original ) {
			return new \WP_Error(
				'invalid_original',
				__( 'The original page no longer exists.', 'mcp-adapter-initializer' ),
				array( 'status' => 404 )
			);
		}

		if ( 'publish' !== $original->post_status ) {
			return new \WP_Error(
				'original_not_published',
				__( 'The original page must be published to merge a draft.', 'mcp-adapter-initializer' ),
				array( 'status' => 400 )
			);
		}

		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'                    => $original_id,
					'post_title'            => $draft->post_title,
					'post_content'          => $draft->post_content,
					'post_excerpt'          => $draft->post_excerpt,
					'menu_order'            => $draft->menu_order,
					'post_password'         => $draft->post_password,
					'comment_status'        => $draft->comment_status,
					'ping_status'           => $draft->ping_status,
					'post_content_filtered' => $draft->post_content_filtered,
				)
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$thumbnail_id = get_post_thumbnail_id( $draft_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $original_id, (int) $thumbnail_id );
		} else {
			delete_post_thumbnail( $original_id );
		}

		self::replace_post_meta( $draft_id, $original_id );

		$removed = self::remove_draft( $draft_id, $force_delete );
		if ( is_wp_error( $removed ) ) {
			// The merge already succeeded; only draft cleanup failed. Wrap the error
			// so callers don't mistake this for a failed publish, while preserving the
			// underlying error code/data for programmatic handling.
			return new \WP_Error(
				$removed->get_error_code(),
				sprintf(
					/* translators: %s: underlying error message. */
					__( 'Draft was published, but removing the draft failed: %s', 'mcp-adapter-initializer' ),
					$removed->get_error_message()
				),
				$removed->get_error_data()
			);
		}

		$linked_draft = (int) get_post_meta( $original_id, self::META_HAS_DRAFT, true );
		if ( $linked_draft === $draft_id ) {
			delete_post_meta( $original_id, self::META_HAS_DRAFT );
		}

		/**
		 * Fires after a draft is merged into its published page and removed.
		 *
		 * @param int $original_id The published post ID.
		 * @param int $draft_id    The draft post ID (may be invalid after deletion).
		 */
		do_action( 'wp_site_designer_draft_published', $original_id, $draft_id );

		return true;
	}

	/**
	 * Discard a draft without merging changes into the original page.
	 *
	 * @param int  $draft_id     The draft post ID.
	 * @param bool $force_delete When false (default), the draft is trashed so it can be restored;
	 *                           when true, it is permanently deleted.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function discard_draft( int $draft_id, bool $force_delete = false ) {
		$draft = get_post( $draft_id );

		if ( ! $draft ) {
			return new \WP_Error(
				'invalid_post',
				__( 'Post not found.', 'mcp-adapter-initializer' ),
				array( 'status' => 404 )
			);
		}

		if ( 'page' !== $draft->post_type ) {
			return new \WP_Error(
				'invalid_post_type',
				__( 'Draft mode is only available for pages.', 'mcp-adapter-initializer' ),
				array( 'status' => 400 )
			);
		}

		if ( 'draft' !== $draft->post_status ) {
			return new \WP_Error(
				'invalid_status',
				__( 'Only draft pages can be discarded from draft mode.', 'mcp-adapter-initializer' ),
				array( 'status' => 400 )
			);
		}

		$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );

		$removed = self::remove_draft( $draft_id, $force_delete );
		if ( is_wp_error( $removed ) ) {
			return $removed;
		}

		if ( $original_id ) {
			$original = get_post( $original_id );
			if ( $original ) {
				$linked_draft = (int) get_post_meta( $original_id, self::META_HAS_DRAFT, true );
				if ( $linked_draft === $draft_id ) {
					delete_post_meta( $original_id, self::META_HAS_DRAFT );
				}
			}
		}

		/**
		 * Fires after a draft is discarded without merging.
		 *
		 * @param int $original_id The published post ID (may be 0 if original was deleted).
		 * @param int $draft_id    The draft post ID (may be invalid after deletion).
		 */
		do_action( 'wp_site_designer_draft_discarded', $original_id, $draft_id );

		return true;
	}

	/**
	 * Remove a draft, either by trashing it (so it can be restored) or by
	 * permanently deleting it.
	 *
	 * When trashing, the draft-linking meta (META_DRAFT_OF / META_DRAFT_CREATED)
	 * is intentionally preserved so the draft can later be restored and re-linked
	 * to its original page.
	 *
	 * @param int  $draft_id     The draft post ID.
	 * @param bool $force_delete Whether to permanently delete (true) or trash (false).
	 * @return true|\WP_Error True on success, WP_Error if the removal failed.
	 */
	private static function remove_draft( int $draft_id, bool $force_delete ) {
		if ( ! $force_delete ) {
			if ( false === wp_trash_post( $draft_id ) ) {
				return new \WP_Error(
					'trash_failed',
					__( 'Failed to move the draft to trash.', 'mcp-adapter-initializer' ),
					array( 'status' => 500 )
				);
			}
			return true;
		}

		if ( false === wp_delete_post( $draft_id, true ) ) {
			return new \WP_Error(
				'delete_failed',
				__( 'Failed to permanently delete the draft.', 'mcp-adapter-initializer' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Clean up draft meta when a post is deleted.
	 *
	 * Hooked to before_delete_post to maintain data integrity.
	 *
	 * @param int $post_id Post ID being deleted.
	 */
	public static function cleanup_draft_meta( int $post_id ): void {
		$original_id = get_post_meta( $post_id, self::META_DRAFT_OF, true );
		if ( $original_id ) {
			$linked_draft = (int) get_post_meta( (int) $original_id, self::META_HAS_DRAFT, true );
			if ( $linked_draft === $post_id ) {
				delete_post_meta( (int) $original_id, self::META_HAS_DRAFT );
			}
		}

		$draft_id = get_post_meta( $post_id, self::META_HAS_DRAFT, true );
		if ( $draft_id ) {
			$draft_post = get_post( $draft_id );

			if ( $draft_post && 'draft' === $draft_post->post_status ) {
				$draft_original_id = get_post_meta( $draft_id, self::META_DRAFT_OF, true );

				if ( (int) $draft_original_id === $post_id ) {
					delete_post_meta( $draft_id, self::META_DRAFT_OF );
					wp_delete_post( $draft_id, true );
				}
			}
		}
	}

	/**
	 * Copy post meta from one post to another.
	 *
	 * @param int $source_id Source post ID.
	 * @param int $target_id Target post ID.
	 */
	public static function copy_post_meta( int $source_id, int $target_id ): void {
		$meta          = get_post_meta( $source_id );
		$excluded_keys = self::get_draft_excluded_meta_keys( $source_id );

		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, $excluded_keys, true ) ) {
				continue;
			}

			foreach ( $values as $value ) {
				$unserialized = maybe_unserialize( $value );
				// Serialized objects are skipped to avoid cloning references to shared
				// instances (e.g., WC product data). This is a known limitation; plugins
				// needing object meta on drafts should use the excluded-keys filter.
				if ( is_object( $unserialized ) ) {
					continue;
				}
				add_post_meta( $target_id, $key, $unserialized );
			}
		}
	}

	/**
	 * Replace all non-excluded meta on the target with meta from the source.
	 *
	 * @param int $source_id Source post ID.
	 * @param int $target_id Target post ID.
	 */
	private static function replace_post_meta( int $source_id, int $target_id ): void {
		$source_meta   = get_post_meta( $source_id );
		$target_meta   = get_post_meta( $target_id );
		$excluded_keys = self::get_draft_excluded_meta_keys( $source_id );

		// Delete target keys that exist on the source — preserves
		// meta that was never copied to the draft (e.g., serialized objects).
		foreach ( array_keys( $target_meta ) as $key ) {
			if ( in_array( $key, $excluded_keys, true ) ) {
				continue;
			}
			if ( ! isset( $source_meta[ $key ] ) ) {
				continue;
			}
			delete_post_meta( $target_id, $key );
		}

		self::copy_post_meta( $source_id, $target_id );
	}

	/**
	 * Meta keys excluded when copying or replacing draft-related post meta.
	 *
	 * @param int $source_id Source post ID (context for filters).
	 * @return array<int, string> Excluded meta keys.
	 */
	private static function get_draft_excluded_meta_keys( int $source_id ): array {
		$excluded_keys = array(
			'_edit_lock',
			'_edit_last',
			self::META_DRAFT_OF,
			self::META_HAS_DRAFT,
			self::META_DRAFT_CREATED,
			'_wp_old_slug',
			'_wp_old_date',
		);

		/**
		 * Filters the meta keys excluded when creating a draft.
		 *
		 * @param array $excluded_keys Keys to exclude from copying.
		 * @param int   $source_id     Source post ID.
		 */
		return apply_filters( 'wp_site_designer_draft_excluded_meta_keys', $excluded_keys, $source_id );
	}

	/**
	 * Handle REST API request to publish a draft.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_publish_draft( \WP_REST_Request $request ) {
		$draft_id    = (int) $request->get_param( 'id' );
		$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );
		$result      = self::publish_draft( $draft_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response(
			array(
				'success'      => true,
				'original_id'  => $original_id,
				'redirect_url' => get_edit_post_link( $original_id, 'raw' ),
			),
			200
		);
	}

	/**
	 * Handle REST API request to discard a draft.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_discard_draft( \WP_REST_Request $request ) {
		$draft_id    = (int) $request->get_param( 'id' );
		$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );
		$result      = self::discard_draft( $draft_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = array(
			'success'      => true,
			'original_id'  => $original_id,
			'redirect_url' => null,
		);
		if ( $original_id > 0 && get_post( $original_id ) ) {
			$response['redirect_url'] = get_edit_post_link( $original_id, 'raw' );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Register REST API routes for browser-based draft operations.
	 *
	 * @return void
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'sd-drafts/v1',
			'/(?P<id>\d+)/publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_publish_draft' ),
				'permission_callback' => function ( \WP_REST_Request $request ) {
					$draft_id    = (int) $request->get_param( 'id' );
					$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );

					if ( ! $original_id || ! current_user_can( 'edit_post', $original_id ) ) {
						return false;
					}

					return current_user_can( 'edit_post', $draft_id );
				},
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'sd-drafts/v1',
			'/(?P<id>\d+)/discard',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( self::class, 'rest_discard_draft' ),
				'permission_callback' => function ( \WP_REST_Request $request ) {
					$draft_id    = (int) $request->get_param( 'id' );
					$original_id = (int) get_post_meta( $draft_id, self::META_DRAFT_OF, true );

					if ( $original_id && ! current_user_can( 'edit_post', $original_id ) ) {
						return false;
					}

					return current_user_can( 'delete_post', $draft_id );
				},
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}
}
