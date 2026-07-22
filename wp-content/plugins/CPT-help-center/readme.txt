=== HELP CENTER ===
Contributors: Ewall developer team
Tags: help center, custom post type, acf, shortcode, search
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A structured Help Center plugin with Custom Post Type support, ACF integration, reusable templates, and searchable shortcode listings.

== Description ==

Help Center is a lightweight WordPress plugin designed to manage and display Help Center / FAQ content in a structured and reusable way.

The plugin registers a dedicated Help Center Custom Post Type and provides frontend shortcodes for rendering searchable listings and individual content blocks using reusable templates.

Built with Advanced Custom Fields (ACF) support, the plugin ensures flexible content management while keeping logic, templates, and rendering cleanly separated.

Key capabilities include:

- Searchable Help Center listings via shortcode
- ACF-powered structured fields
- Clean, scalable architecture following WordPress coding standards

The plugin is safe to use across pages, posts, and custom post types.

== Features ==

* Custom Post Type for Help Center content
* Search-enabled listing shortcode
* ACF-powered structured fields
* Clean separation of logic and templates
* Lightweight and developer-friendly architecture

== Shortcodes ==

[help_center_listing]

Displays all Help Center posts with built-in search functionality and template-based rendering.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Ensure **Advanced Custom Fields (ACF)** is installed and active
4. Assign the provided ACF field groups to the Help Center post type
5. Add the `[help_center_listing]` shortcode to any page to display listings

== Requirements ==

- WordPress 6.0+
- PHP 7.4+
- Advanced Custom Fields (ACF)

