<?php
/**
 * Block Markup Annotator
 *
 * Frontend rendered HTML has no block identity and `wp.blocks` isn't loaded, so
 * the element picker can't serialize a clicked block itself. This stamps a
 * `data-sd-block-id` on each block's outermost tag at render time and stashes an
 * `id => { name, markup }` map in a short-lived per-user transient, served to
 * the picker on demand via REST (keyed by a small per-render token printed in
 * the footer). Stashing rather than inlining keeps the map — which can be
 * several times the page's block source — out of every editor's page DOM.
 *
 * Markup is captured at render time, not resolved by path later, because render
 * expands synced patterns / reusable blocks / template parts; a position
 * resolved against post_content afterwards can target the wrong block.
 *
 * Frontend only, for users who can edit content — anonymous visitors get nothing.
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions\Utils\Rate_Limiter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Annotates rendered blocks with a stable id and serves their markup on demand.
 */
class Block_Markup_Annotator {

	/**
	 * Capability required to receive block annotations. Mirrors Native_UI_Loader.
	 */
	private const REQUIRED_CAPABILITY = 'edit_posts';

	/**
	 * Attribute stamped on each annotated block's outermost rendered tag.
	 */
	private const ID_ATTRIBUTE = 'data-sd-block-id';

	/**
	 * DOM id of the JSON script tag carrying the per-render map token.
	 */
	private const KEY_ELEMENT_ID = 'sd-block-map-key';

	/**
	 * Prefix for the per-user transient that holds the block map.
	 */
	private const TRANSIENT_PREFIX = 'sd_block_map_';

	/**
	 * How long a render's block map is retrievable, in seconds. The picker
	 * fetches it immediately after page load, so this only needs to outlive the
	 * gap between load and the first selection.
	 */
	private const TRANSIENT_TTL = 300;

	/**
	 * Per-request map of block id => array{ name: string, markup: string }.
	 *
	 * @var array<string, array{name: string, markup: string}>
	 */
	private array $map = array();

	/**
	 * Monotonic counter used to mint block ids in render order.
	 *
	 * @var int
	 */
	private int $counter = 0;

	/**
	 * Token identifying this render's map. Lets the picker fetch the exact map
	 * the ids on the page belong to (disambiguates concurrent tabs for one user).
	 *
	 * @var string|null
	 */
	private ?string $token = null;

	/**
	 * Memoized gate result for the current request.
	 *
	 * @var bool|null
	 */
	private ?bool $can_annotate = null;

	/**
	 * Initialize and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_filter( 'render_block', array( $instance, 'annotate_block' ), 10, 2 );
		add_action( 'wp_footer', array( $instance, 'store_block_map' ) );
		add_action( 'rest_api_init', array( $instance, 'register_routes' ) );
	}

	/**
	 * Stamp the rendered block with a stable id and record its serialized markup.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The parsed block (blockName, attrs, innerBlocks, ...).
	 * @return string The (possibly) annotated block HTML.
	 */
	public function annotate_block( string $block_content, array $block ): string {
		if ( ! $this->should_annotate() ) {
			return $block_content;
		}

		// Skip classic/freeform fragments (null blockName) and empty output —
		// there is no meaningful block to target.
		if ( empty( $block['blockName'] ) || '' === trim( $block_content ) ) {
			return $block_content;
		}

		$annotated = $this->inject_block_id( $block_content, 'sdb-' . $this->counter );
		if ( $annotated === $block_content ) {
			// No leading HTML tag to stamp (e.g. a block that renders bare text);
			// leave it untouched so the picker falls back to DOM context.
			return $block_content;
		}

		$this->map[ 'sdb-' . $this->counter ] = array(
			'name'   => (string) $block['blockName'],
			'markup' => serialize_block( $block ),
		);
		++$this->counter;

		return $annotated;
	}

	/**
	 * Stash the accumulated block map in a per-user transient and print the
	 * token the picker uses to fetch it. Runs once, in the footer, after all
	 * blocks have rendered.
	 *
	 * @return void
	 */
	public function store_block_map(): void {
		if ( ! $this->should_annotate() || empty( $this->map ) ) {
			return;
		}

		$token = $this->get_token();
		set_transient( $this->transient_key( $token ), $this->map, self::TRANSIENT_TTL );

		printf(
			'<script type="application/json" id="%s">%s</script>',
			esc_attr( self::KEY_ELEMENT_ID ),
			wp_json_encode( array( 'key' => $token ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inert JSON; key is a generated UUID, not user input.
		);
	}

	/**
	 * Register the REST route the picker calls to fetch a render's block map.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'wp-site-designer/v1',
			'/block-map',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_block_map' ),
				'args'                => array(
					'key' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => function () {
					if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
						return false;
					}
					$identifier = 'block_map_' . get_current_user_id();
					if ( ! Rate_Limiter::check( $identifier, 60, 60 ) ) {
						return new WP_Error( 'rate_limit_exceeded', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
					}
					return true;
				},
			)
		);
	}

	/**
	 * Return the block map for the given render token.
	 *
	 * The transient is keyed by the current user id, so a token alone cannot
	 * read another user's map. Returns an empty map (not an error) when the
	 * token is unknown or expired so the picker degrades to DOM-only context.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function get_block_map( WP_REST_Request $request ): WP_REST_Response {
		$token = (string) $request->get_param( 'key' );
		$map   = '' === $token ? false : get_transient( $this->transient_key( $token ) );

		return new WP_REST_Response(
			array( 'blocks' => is_array( $map ) ? $map : array() ),
			200
		);
	}

	/**
	 * Whether block annotation should run for the current request.
	 *
	 * Frontend only, and only for users who can edit content (the same audience
	 * that loads the native UI). Memoized since render_block fires per block.
	 *
	 * @return bool
	 */
	private function should_annotate(): bool {
		if ( null === $this->can_annotate ) {
			$this->can_annotate = ! is_admin() && current_user_can( self::REQUIRED_CAPABILITY );
		}

		return $this->can_annotate;
	}

	/**
	 * Lazily mint and memoize this render's map token.
	 *
	 * @return string
	 */
	private function get_token(): string {
		if ( null === $this->token ) {
			$this->token = wp_generate_uuid4();
		}

		return $this->token;
	}

	/**
	 * Build the transient key for a token, scoped to the current user.
	 *
	 * @param string $token The render token.
	 * @return string
	 */
	private function transient_key( string $token ): string {
		return self::TRANSIENT_PREFIX . get_current_user_id() . '_' . $token;
	}

	/**
	 * Inject the block id attribute onto the first HTML tag of the rendered output.
	 *
	 * Prefers WP_HTML_Tag_Processor (WP 6.2+) for correct attribute handling and
	 * falls back to a single-tag regex when it is unavailable.
	 *
	 * @param string $html The rendered block HTML.
	 * @param string $id   The block id to stamp.
	 * @return string HTML with the id attribute, or the original HTML if no tag was found.
	 */
	private function inject_block_id( string $html, string $id ): string {
		if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $html );
			if ( $processor->next_tag() ) {
				$processor->set_attribute( self::ID_ATTRIBUTE, $id );
				return $processor->get_updated_html();
			}
			return $html;
		}

		// Fallback: stamp the first opening tag (e.g. "<h2", "<div ", "<img/").
		// The letter requirement after "<" skips comments and text nodes.
		$replaced = preg_replace(
			'/<([a-zA-Z][a-zA-Z0-9-]*)(\s|>|\/)/',
			'<$1 ' . self::ID_ATTRIBUTE . '="' . esc_attr( $id ) . '"$2',
			$html,
			1
		);

		return is_string( $replaced ) ? $replaced : $html;
	}
}
