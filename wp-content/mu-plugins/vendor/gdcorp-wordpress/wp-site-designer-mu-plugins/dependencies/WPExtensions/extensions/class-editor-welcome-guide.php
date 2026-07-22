<?php
/**
 * Editor Welcome Guide
 *
 * Disables the Gutenberg block editor welcome/tutorial modal when
 * native UI is active. Covers both the post editor and site editor (FSE)
 * across multiple WordPress versions.
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disables the Gutenberg welcome guide modal on block editor pages.
 */
class Editor_Welcome_Guide {

	/**
	 * Initialize the class and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_action( 'enqueue_block_editor_assets', array( $instance, 'disable_welcome_guide' ) );
	}

	/**
	 * Disable the welcome guide by injecting JavaScript into the block editor.
	 *
	 * @return void
	 */
	public function disable_welcome_guide(): void {
		$script = <<<'JS'
( function () {
	if ( typeof wp === 'undefined' || ! wp.data?.dispatch || ! wp.data?.select ) {
		return;
	}

	var dispatch = wp.data.dispatch;
	var select = wp.data.select;

	// Disable via preferences store (WP 6.0+).
	var prefsDispatch = dispatch('core/preferences');
	if (prefsDispatch && prefsDispatch.set) {
		['core/edit-post', 'core/edit-site'].forEach(function(store) {
			try { prefsDispatch.set(store, 'welcomeGuide', false); } catch(e) {}
		});
	}

	// Legacy toggle fallback.
	var editPostStore = select('core/edit-post');
	if (editPostStore && editPostStore.isFeatureActive && editPostStore.isFeatureActive('welcomeGuide')) {
		dispatch('core/edit-post').toggleFeature('welcomeGuide');
	}

	var editSiteStore = select('core/edit-site');
	if (editSiteStore && editSiteStore.isFeatureActive && editSiteStore.isFeatureActive('welcomeGuide')) {
		dispatch('core/edit-site').toggleFeature('welcomeGuide');
	}
} )();
JS;

		// Post/page editor.
		if ( wp_script_is( 'wp-edit-post', 'enqueued' ) ) {
			wp_add_inline_script( 'wp-edit-post', $script, 'before' );
		}

		// Site editor (FSE).
		if ( wp_script_is( 'wp-edit-site', 'enqueued' ) ) {
			wp_add_inline_script( 'wp-edit-site', $script, 'before' );
		}
	}
}
