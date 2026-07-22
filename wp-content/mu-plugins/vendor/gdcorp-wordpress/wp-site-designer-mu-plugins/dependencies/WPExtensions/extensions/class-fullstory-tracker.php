<?php
/**
 * FullStory Tracker
 *
 * Injects the FullStory session recording snippet on WordPress pages when
 * native UI is active. Runs as a standalone session (not connected to a
 * parent iframe session). Calls FS.identify with GD_CUSTOMER_ID the same way
 * as WPaaS system plugin RUM (constant + wp_is_uuid validation).
 *
 * @package gdcorp-wordpress/site-designer-ui-extensions
 */

declare( strict_types=1 );

namespace GoDaddy\WordPress\Plugins\SiteDesigner\Dependencies\WPExtensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects FullStory snippet for session recording on native UI pages.
 */
class FullStory_Tracker {

	const FULLSTORY_ORG_ID = 'YKBRC';

	/**
	 * Initialize the class and register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();

		add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_snippet' ) );
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_snippet' ) );
		add_filter( 'admin_body_class', array( $instance, 'add_unmask_admin_body_class' ) );
		add_filter( 'body_class', array( $instance, 'add_unmask_body_class' ) );
	}


	/**
	 * Add fs-unmask class to frontend/iframe body tag.
	 *
	 * @param array $classes Existing body classes.
	 *
	 * @return array Modified body classes.
	 */
	public function add_unmask_body_class( array $classes ): array {
		$classes[] = 'fs-unmask';
		return $classes;
	}

	/**
	 * Add fs-unmask class to admin body tag.
	 *
	 * @param string|null $classes Existing admin body classes.
	 *
	 * @return string Modified admin body classes.
	 */
	public function add_unmask_admin_body_class( ?string $classes ): string {
		return trim( ( $classes ?? '' ) . ' fs-unmask' );
	}

	/**
	 * Enqueue the FullStory bootstrap snippet via WordPress script API.
	 *
	 * Uses wp_register_script + wp_add_inline_script so the snippet is
	 * properly loaded inside the block editor iframe (raw echo only
	 * outputs to the outer admin page).
	 *
	 * @return void
	 */
	public function enqueue_snippet(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( wp_script_is( 'fullstory-tracker', 'enqueued' ) ) {
			return;
		}

		$org_id = esc_js( self::FULLSTORY_ORG_ID );

		$snippet = <<<JS
if (window !== window.top) {
  if (document.body) { document.body.classList.add('fs-unmask'); }
  else { document.addEventListener('DOMContentLoaded', function() { document.body.classList.add('fs-unmask'); }); }
}

window['_fs_debug'] = false;
window['_fs_host'] = 'fullstory.com';
window['_fs_script'] = 'edge.fullstory.com/s/fs.js';
window['_fs_org'] = '{$org_id}';
window['_fs_namespace'] = 'FS';
window['_fs_capture_on_startup'] = true;

(function(m,n,e,t,l,o,g,y){
if (e in m) {return;}
g=m[e]=function(a,b,s){g.q?g.q.push([a,b,s]):g._api(a,b,s);};g.q=[];
o=n.createElement(t);o.async=1;o.crossOrigin='anonymous';o.src='https://'+_fs_script;
y=n.getElementsByTagName(t)[0];y.parentNode.insertBefore(o,y);
g.identify=function(i,v,s){g(l,{uid:i},s);if(v)g(l,v,s)};g.setUserVars=function(v,s){g(l,v,s)};g.event=function(i,v,s){g('event',{n:i,p:v},s)};
g.anonymize=function(){g.identify(!!0)};
g.shutdown=function(){g("rec",!1)};g.restart=function(){g("rec",!0)};
g.log = function(a,b){g("log",[a,b])};
g.consent=function(a){g("consent",!arguments.length||a)};
g.identifyAccount=function(i,v){o='account';v=v||{};v.acctId=i;g(o,v)};
g.clearUserCookie=function(){};
g.setVars=function(n,p){g('setVars',[n,p]);};
g._w={};
y='XMLHttpRequest';g._w[y]=m[y];y='fetch';g._w[y]=m[y];
if(m[y])m[y]=function(){return g._w[y].apply(this,arguments)};
})(window,document,window['_fs_namespace'],'script','user');
JS;

		wp_register_script( 'fullstory-tracker', false, array(), '1.0.0', false );
		wp_add_inline_script( 'fullstory-tracker', $snippet );

		// Same identify pattern as WPaaS RUM (class-rum.php): GD_CUSTOMER_ID + wp_is_uuid.
		if ( defined( 'GD_CUSTOMER_ID' ) && wp_is_uuid( (string) GD_CUSTOMER_ID ) ) {
			$uid   = esc_js( (string) GD_CUSTOMER_ID );
			$ident = 'if(typeof FS!==\'undefined\'&&FS.identify){FS.identify(\'' . $uid . '\');}';
			wp_add_inline_script( 'fullstory-tracker', $ident, 'after' );
		}

		wp_enqueue_script( 'fullstory-tracker' );
	}
}
