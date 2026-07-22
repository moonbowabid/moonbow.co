=== AI Suite Display ===
Contributors: Ewall developer team
Tags: ai suite, custom post type, acf, grid
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A structured solution to manage and display AI Suite content using ACF-powered fields and templates.

== Description ==

The AI Suite implementation provides a flexible way to manage AI-related
content using a Custom Post Type and Advanced Custom Fields (ACF).

Each AI Suite item has a dedicated detail page with structured sections,
similar to Services, but simplified and optimized for AI Suite content.

This implementation uses a **grid-based layout** and does not include carousel logic.

== Features ==

* Custom Post Type support (AI Suite)
* ACF-based structured content
* Clean grid-based layout
* Dynamic single page template
* Inline SVG icon support
* Integration with Case Studies and FAQ
* Lightweight and maintainable structure

== AI Suite Detail Page Structure ==

Each AI Suite page includes:

1. **Breadcrumb**
   - Links back to AI Suite listing page

2. **Title Section**
   - Page title
   - Featured image
   - Excerpt displayed as overlay text

3. **Overview Section**
   - Title
   - Content
   - Optional banner image
   - Layout adjusts if image is missing

4. **Key Services / Features**
   - Section title and intro
   - Repeater-based list
   - Supports:
     - Dynamic points (point_1, point_2, etc.)
     - Optional icons (SVG or uploaded)

5. **Benefits and Outcomes Section**
   - Title and content
   - Optional image
   - Structured list with:
     - Title
     - Description
     - Icon (inline SVG supported)

6. **Related Case Studies**
   - Rendered using shortcode:
     ```text
     [related_case_studies]
     ```
   - Displays selected or fallback case studies

7. **FAQ Section**
   - Rendered using shortcode:
     ```text
     [render_faq]
     ```

== ACF Field Groups ==

Key field groups used:

* `ai-suite_detail_block`
  - Custom title override (optional)

* `ai-suite_overview_block`
  - Overview title
  - Content
  - Banner image

* `key_services_block`
  - Title
  - Intro content
  - Repeater list of key points with optional icons

* `ai-suite_benefits_and_outcomes`
  - Title
  - Content
  - Image
  - Structured list (title, content, icon)

== Notes ==

* Featured image is required for best layout appearance.
* Icons support inline SVG rendering for better control.
* Layout automatically adjusts based on available content.
* Related Case Studies logic is shared with global implementation.
* No carousel or slider is used in AI Suite.
