=== Services Display ===
Contributors: Ewall developer team
Tags: services, custom post type, acf, shortcode, grid
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A dynamic solution to manage and display Services using ACF fields, templates, and shortcode-based rendering.

== Description ==

The Services implementation provides a structured way to manage and display
service-related content using a Custom Post Type (CPT) and Advanced Custom Fields (ACF).

It includes:

- A **Services listing grid** (via shortcode)
- A **detailed Service single page template**
- Integration with **FAQ**, **Related Case Studies**, and **Related Services**

All content is controlled via ACF fields, allowing flexible content management.

== Features ==

* Custom Post Type: `service`
* ACF-powered structured content blocks
* Shortcode-based Services listing
* Fully dynamic Service detail page template
* Integration with related Case Studies and FAQ
* Breadcrumb navigation support
* Video banner with fallback poster support
* Clean and extendable markup
* No unnecessary dependencies

== Shortcode ==

```text
[render_services]