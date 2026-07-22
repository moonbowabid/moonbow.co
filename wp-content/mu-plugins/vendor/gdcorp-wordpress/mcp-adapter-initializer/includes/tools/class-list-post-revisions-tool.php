<?php
/**
 * List Post Revisions Tool Class
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
 * List Post Revisions Tool
 *
 * Handles the registration and execution of the list post revisions ability
 * for the MCP adapter. Works across any post type (page, post, wp_navigation,
 * etc.) by running a WP_Query against the `revision` post type filtered by
 * `post_parent`, so pagination, totals, search, and include/exclude are
 * computed at the DB layer in a single round-trip. Matches the generic nature
 * of its sibling tool gd-mcp/restore-post-revision.
 *
 * WP_Query is used instead of wp_get_post_revisions() because the latter
 * returns a pre-sliced array with no found_posts / max_num_pages, which
 * makes accurate total / total_pages impossible without a second query,
 * and it does not support search or include/exclude arguments.
 */
class List_Post_Revisions_Tool extends Base_Tool {

	/**
	 * Tool identifier
	 *
	 * @var string
	 */
	const TOOL_ID = 'gd-mcp/list-post-revisions';

	/**
	 * Tool instance
	 *
	 * @var List_Post_Revisions_Tool|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return List_Post_Revisions_Tool
	 */
	public static function get_instance(): List_Post_Revisions_Tool {
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
	 * Register the list post revisions ability
	 *
	 * @return void
	 */
	public function register(): void {
		wp_register_ability(
			self::TOOL_ID,
			array(
				'label'               => __( 'List Post Revisions', 'mcp-adapter-initializer' ),
				'description'         => __( 'Retrieves a list of revisions for a post of any type (page, post, wp_navigation, etc.) with filtering and pagination options', 'mcp-adapter-initializer' ),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute_with_admin' ),
				'permission_callback' => '__return_true',
				'category'            => 'content-management',
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
				'parent'   => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the parent post (any post type) for the revisions', 'mcp-adapter-initializer' ),
					'minimum'     => 1,
				),
				'context'  => array(
					'type'        => 'string',
					'description' => __( 'Scope under which the request is made; determines fields present in response', 'mcp-adapter-initializer' ),
					'enum'        => array( 'view', 'embed', 'edit' ),
					'default'     => 'view',
				),
				'page'     => array(
					'type'        => 'integer',
					'description' => __( 'Current page of the collection', 'mcp-adapter-initializer' ),
					'default'     => 1,
					'minimum'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => __( 'Maximum number of items to be returned in result set', 'mcp-adapter-initializer' ),
					'default'     => 10,
					'minimum'     => 1,
					'maximum'     => 100,
				),
				'search'   => array(
					'type'        => 'string',
					'description' => __( 'Limit results to those matching a string', 'mcp-adapter-initializer' ),
				),
				'exclude'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => __( 'Ensure result set excludes specific revision IDs', 'mcp-adapter-initializer' ),
				),
				'include'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => __( 'Limit result set to specific revision IDs', 'mcp-adapter-initializer' ),
				),
				'offset'   => array(
					'type'        => 'integer',
					'description' => __( 'Offset the result set by a specific number of items. When provided, takes precedence over `page` — the two are mutually exclusive in WP_Query and combining them breaks pagination totals.', 'mcp-adapter-initializer' ),
					'minimum'     => 0,
				),
				'order'    => array(
					'type'        => 'string',
					'description' => __( 'Order sort attribute ascending or descending', 'mcp-adapter-initializer' ),
					'enum'        => array( 'asc', 'desc' ),
					'default'     => 'desc',
				),
				'orderby'  => array(
					'type'        => 'string',
					'description' => __( 'Sort collection by object attribute', 'mcp-adapter-initializer' ),
					'enum'        => array( 'date', 'id', 'include', 'relevance', 'slug', 'include_slugs', 'title' ),
					'default'     => 'date',
				),
			),
			'required'   => array( 'parent' ),
		);
	}

	/**
	 * Get output schema for the tool
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'revisions'   => array(
					'type'        => 'array',
					'description' => __( 'Array of revision objects', 'mcp-adapter-initializer' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'            => array(
								'type'        => 'integer',
								'description' => __( 'The revision ID', 'mcp-adapter-initializer' ),
							),
							'author_id'     => array(
								'type'        => 'integer',
								'description' => __( 'The revision author ID', 'mcp-adapter-initializer' ),
							),
							'date_created'  => array(
								'type'        => 'string',
								'description' => __( 'The revision creation date', 'mcp-adapter-initializer' ),
							),
							'date_modified' => array(
								'type'        => 'string',
								'description' => __( 'The revision modification date', 'mcp-adapter-initializer' ),
							),
							'parent_id'     => array(
								'type'        => 'integer',
								'description' => __( 'The parent post ID', 'mcp-adapter-initializer' ),
							),
							'slug'          => array(
								'type'        => 'string',
								'description' => __( 'The revision slug', 'mcp-adapter-initializer' ),
							),
							'title'         => array(
								'type'        => 'string',
								'description' => __( 'The revision title', 'mcp-adapter-initializer' ),
							),
							'content'       => array(
								'type'        => 'string',
								'description' => __( 'The revision content', 'mcp-adapter-initializer' ),
							),
							'excerpt'       => array(
								'type'        => 'string',
								'description' => __( 'The revision excerpt', 'mcp-adapter-initializer' ),
							),
						),
					),
				),
				'total'       => array(
					'type'        => 'integer',
					'description' => __( 'Total number of revisions', 'mcp-adapter-initializer' ),
				),
				'total_pages' => array(
					'type'        => 'integer',
					'description' => __( 'Total number of pages', 'mcp-adapter-initializer' ),
				),
				'page'        => array(
					'type'        => 'integer',
					'description' => __( 'Current page number', 'mcp-adapter-initializer' ),
				),
				'per_page'    => array(
					'type'        => 'integer',
					'description' => __( 'Number of items per page', 'mcp-adapter-initializer' ),
				),
			),
		);
	}

	/**
	 * Execute the list post revisions tool
	 *
	 * @param array $input Input parameters
	 * @return array List of revisions or error
	 */
	public function execute( array $input ): array {
		// Validate required parameters
		if ( empty( $input['parent'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Parent post ID is required', 'mcp-adapter-initializer' ),
			);
		}

		$parent_id = (int) $input['parent'];

		// Check if parent post exists — any post type is valid.
		$parent_post = get_post( $parent_id );
		if ( ! $parent_post ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Post with ID %d not found', 'mcp-adapter-initializer' ), $parent_id ),
			);
		}

		// Build WP_Query args so pagination and filtering happen at the DB layer.
		// Mirrors List_Navigation_Revisions_Tool — wp_get_post_revisions() returns an
		// already-sliced result, which makes accurate total / total_pages impossible.
		$per_page   = isset( $input['per_page'] ) ? (int) $input['per_page'] : 10;
		$page       = isset( $input['page'] ) ? (int) $input['page'] : 1;
		$use_offset = isset( $input['offset'] );
		$offset     = $use_offset ? (int) $input['offset'] : 0;
		$has_search = ! empty( $input['search'] );

		$args = array(
			'post_type'      => 'revision',
			'post_parent'    => $parent_id,
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'orderby'        => $this->map_orderby( isset( $input['orderby'] ) ? $input['orderby'] : 'date', $has_search ),
			'order'          => isset( $input['order'] ) ? strtoupper( $input['order'] ) : 'DESC',
		);

		// `offset` and `paged` are mutually exclusive in WP_Query — combining them
		// silently breaks found_posts / max_num_pages. Honor offset exclusively
		// when provided, otherwise use page-based pagination.
		if ( $use_offset ) {
			$args['offset'] = $offset;
		} else {
			$args['paged'] = $page;
		}

		if ( $has_search ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}
		if ( ! empty( $input['include'] ) && is_array( $input['include'] ) ) {
			$args['post__in'] = array_map( 'intval', $input['include'] );
		}
		if ( ! empty( $input['exclude'] ) && is_array( $input['exclude'] ) ) {
			$args['post__not_in'] = array_map( 'intval', $input['exclude'] );
		}

		$query   = new \WP_Query( $args );
		$context = isset( $input['context'] ) ? $input['context'] : 'view';

		$revision_data = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$revision_data[] = $this->format_revision( get_post(), $context );
			}
			wp_reset_postdata();
		}

		// Report the effective page so the response is internally consistent
		// regardless of which pagination mode the caller used.
		$effective_page = $use_offset
			? ( (int) floor( $offset / max( 1, $per_page ) ) + 1 )
			: $page;

		return array(
			'revisions'   => $revision_data,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $effective_page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Translate the schema's public `orderby` enum to a WP_Query-native key.
	 *
	 * The schema mirrors the WP REST API revisions controller, whose enum
	 * includes values WP_Query does not accept verbatim (`id`, `slug`,
	 * `include`, `include_slugs`). Passing those through unchanged causes
	 * WP_Query to silently fall back to default ordering. `relevance` is
	 * only meaningful when a search term is supplied — drop it otherwise.
	 *
	 * @param string $orderby    Raw orderby value from input.
	 * @param bool   $has_search Whether the query carries a search term.
	 * @return string WP_Query-compatible orderby value.
	 */
	private function map_orderby( string $orderby, bool $has_search ): string {
		$map = array(
			'date'          => 'date',
			'id'            => 'ID',
			'slug'          => 'name',
			'title'         => 'title',
			'include'       => 'post__in',
			'include_slugs' => 'post_name__in',
			'relevance'     => 'relevance',
		);

		$mapped = isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'date';

		if ( 'relevance' === $mapped && ! $has_search ) {
			return 'date';
		}

		return $mapped;
	}

	/**
	 * Format revision data based on context
	 *
	 * @param \WP_Post $revision Revision post object
	 * @param string   $context  Context (view, embed, edit)
	 * @return array Formatted revision data
	 */
	private function format_revision( \WP_Post $revision, string $context ): array {
		$data = array(
			'id'            => $revision->ID,
			'author_id'     => (int) $revision->post_author,
			'date_created'  => $revision->post_date,
			'date_modified' => $revision->post_modified,
			'parent_id'     => (int) $revision->post_parent,
			'slug'          => $revision->post_name,
		);

		// Add fields based on context
		switch ( $context ) {
			case 'embed':
				$data['title']   = $revision->post_title;
				$data['excerpt'] = wp_trim_words( $revision->post_excerpt ? $revision->post_excerpt : $revision->post_content, 55 );
				break;

			case 'edit':
				$data['title']   = $revision->post_title;
				$data['content'] = $revision->post_content;
				$data['excerpt'] = $revision->post_excerpt;
				break;

			case 'view':
			default:
				$data['title']   = $revision->post_title;
				$data['content'] = apply_filters( 'the_content', $revision->post_content );
				$data['excerpt'] = $revision->post_excerpt;
				break;
		}

		return $data;
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
