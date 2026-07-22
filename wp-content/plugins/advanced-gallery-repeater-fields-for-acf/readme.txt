=== Advanced Gallery & Repeater Fields for ACF ===
Contributors: uxdexperts
Tags: acf, gallery, repeater, custom fields, acf addon
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 2.1.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced ACF fields with built-in layouts. Works with free ACF! Gallery & Repeater fields with masonry, carousel, lightbox & more.

== Description ==

**Advanced Gallery & Repeater Fields for ACF** supercharges Advanced Custom Fields with two powerful field types that include built-in frontend display options. No coding required!

= Enhanced Gallery Field =

Transform your image galleries with professional layouts - no coding needed!

**Available Layouts:**
* **Grid** - Responsive grid with customizable columns (1-6)
* **Masonry** - Pinterest-style masonry layout
* **Carousel/Slider** - Touch-enabled slider with autoplay, arrows, and dots
* **Justified** - Flickr-style justified rows with perfect alignment
* **Lightbox** - Built-in lightbox modal with thumbnails, keyboard navigation, and swipe support

**Gallery Features:**
* WordPress Media Library integration
* Drag & drop image reordering
* Configurable image sizes
* Lazy loading for performance
* Caption support
* Touch/swipe gestures
* Keyboard navigation
* Fully responsive

= 📋 Enhanced Repeater Field =

Create beautiful repeating content sections with pre-built templates.

** Works with ACF Free & Pro!** - No ACF Pro required for admin editing.

**Available Layouts:**
* **List** - Clean, simple list format
* **Grid/Cards** - Modern card-based grid with images, titles, and CTAs
* **Table** - Organized data table format
* **Accordion** - Collapsible panels with ARIA accessibility
* **Tabs** - Horizontal or vertical tabbed interface
* **Timeline** - Vertical timeline with dates and markers

**Repeater Features:**
* Visual admin interface (no ACF Pro needed!)
* Add, remove, duplicate, and reorder rows
* Field mapping for titles, content, images, links, and dates
* ARIA accessibility support
* Keyboard navigation
* Responsive layouts
* Customizable styling

= Key Features =

* **Works with ACF Free!** - Both fields fully functional with free ACF
* **Zero Coding** - Configure everything in field settings
* **Auto-Rendering** - Use standard `the_field()` or `get_field()`
* **Fully Responsive** - Mobile-friendly layouts
* **Fast Performance** - Optimized CSS and JS
* **Accessible** - ARIA labels and keyboard navigation
* **WP All Import Compatible** - Bulk import data from CSV/XML
* **Developer Friendly** - Helper functions and CSS variables for customization
* **SEO Optimized** - Semantic HTML markup
* **Translation Ready** - Full i18n support

= Perfect For =

* Photography portfolios
* Product galleries
* Team member listings
* Testimonials
* FAQs
* Services showcases
* Timeline histories
* Feature comparisons
* And much more!

= WP All Import Compatibility =

Fully compatible with WP All Import Pro and the ACF Add-On:
* Enhanced Gallery: Import comma-separated image IDs or URLs
* Enhanced Repeater: Import JSON or serialized data
* Automatic field type mapping
* Bulk data migration support

= Usage Examples =

**Basic Usage:**
`
<?php
// Auto-renders based on field settings
the_field('my_gallery');
?>
`

**Helper Functions:**
`
<?php
// Manual rendering with full control
echo agrfuxd_render_enhanced_gallery('gallery_field', get_the_ID());
echo agrfuxd_render_enhanced_repeater('repeater_field', get_the_ID());
?>
`

**Custom Styling:**
`
:root {
    --agrfuxd-columns: 4;
    --agrfuxd-gap: 20px;
    --agrfuxd-primary: #0073aa;
    --agrfuxd-radius: 10px;
}
`

= Browser Support =

* Chrome (latest)
* Firefox (latest)
* Safari (latest)
* Edge (latest)
* Mobile browsers (iOS Safari, Chrome Mobile)

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for "Advanced Gallery Repeater Fields ACF"
4. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/advanced-gallery-repeater-fields-for-acf`
3. Activate through the **Plugins** menu in WordPress

= Setup Instructions =

1. Ensure Advanced Custom Fields (Free or Pro) is installed and activated
2. Create a new Field Group in **Custom Fields → Field Groups**
3. Add an **Enhanced Gallery** or **Enhanced Repeater** field
4. Configure your desired layout in the field settings
5. Assign the field group to your post types
6. Use `<?php the_field('field_name'); ?>` in your template files

== Frequently Asked Questions ==

= Does this require ACF Pro? =

**No!** As of version 1.2.0, both Enhanced Gallery and Enhanced Repeater fields work perfectly with **ACF Free**. The repeater field now includes a custom admin interface that doesn't require ACF Pro.

= How do I display the fields on my website? =

Simply use ACF's standard template tags:
`
<?php the_field('your_field_name'); ?>
`

The plugin automatically renders the field with the layout you configured in the field settings.

= Can I customize the styling? =

Yes! The plugin uses CSS variables for easy customization. Add this to your theme's CSS:
`
:root {
    --agrfuxd-columns: 3;
    --agrfuxd-gap: 15px;
    --agrfuxd-primary: #your-color;
    --agrfuxd-radius: 8px;
}
`

You can also override any CSS class with your theme stylesheet.

= Can I use this with WP All Import? =

Absolutely! The plugin is fully compatible with WP All Import Pro:
* **Gallery fields:** Import comma-separated image IDs like `123,456,789`
* **Repeater fields:** Import JSON data like `[{"title":"Item 1"},{"title":"Item 2"}]`

= How do I add sub-fields to the Enhanced Repeater? =

In the ACF Field Group editor:
1. Add an Enhanced Repeater field
2. Click the **+ Add Field** button that appears below it
3. Add your sub-fields (text, image, textarea, etc.)
4. These sub-fields will appear as columns in the repeater

= Does it support lazy loading for images? =

Yes! The Enhanced Gallery includes built-in lazy loading support. Enable it in the field settings to improve page load performance.

= Is it accessible? =

Yes! All interactive elements include proper ARIA labels, keyboard navigation support, and semantic HTML for screen readers.

= Can I use multiple galleries on one page? =

Yes! You can use as many Enhanced Gallery and Enhanced Repeater fields as you need on a single page. Each instance gets a unique ID to prevent conflicts.

= Does it work with page builders? =

Yes! Full native support with **Elementor** and other page builders:

**Elementor Integration:**
* ✅ Enhanced Gallery appears in ACF Gallery widget (Elementor Pro)
* ✅ Enhanced Repeater appears in ACF Repeater widget (Elementor Pro)
* ✅ Works with Loop Grid and Dynamic Tags
* ✅ Shortcode support: `[agrfuxd_gallery field="your_field"]`
* ✅ See ELEMENTOR-INTEGRATION.md for detailed guide

**Other Page Builders:**
* Beaver Builder - Full ACF support
* Oxygen Builder - ACF integration
* Bricks Builder - ACF widgets
* Gutenberg - ACF blocks
* Any builder that supports ACF fields

= What image sizes are supported? =

The gallery field supports all WordPress image sizes:
* Thumbnail
* Medium
* Medium Large
* Large
* Full Size
* Any custom sizes registered by your theme

= Can I export/import field groups? =

Yes! ACF's native export/import functionality works perfectly with these field types.

= Where can I get support? =

For support requests, please visit our [support forum](https://uxdesignexperts.com/support/) or [GitHub repository](https://github.com/uxdexperts/advanced-gallery-repeater-fields-acf).

== Screenshots ==

1. Enhanced Gallery field in admin - Configure layouts, columns, gaps, and lightbox options
2. Gallery grid layout on frontend - Responsive grid with customizable columns
3. Gallery masonry layout - Pinterest-style masonry with variable heights
4. Gallery carousel layout - Touch-enabled slider with autoplay and navigation
5. Built-in lightbox - Full-screen image viewing with thumbnails and keyboard navigation
6. Enhanced Repeater field in admin - Add, remove, duplicate, and reorder rows without ACF Pro

== Changelog ==

= 2.1.5 - 2026-05-05 =
**Added Enhanced Sub-Field Helper**

* **NEW:** `agrfuxd_get_sub_field()` helper function for easier sub-field access in repeater loops
* **IMPROVED:** Code examples in settings page now use the new helper function
* **IMPROVED:** Better sub-field value formatting (auto-handles images, files, arrays)

= 2.1.4 - 2026-05-05 =
**Fixed Revision History Warning**

* **FIXED:** "Array to string conversion" warning when viewing post revision history for Enhanced Repeater fields
* **FIXED:** Added custom revision field formatting to convert nested repeater data into readable string format
* **IMPROVED:** Enhanced Repeater fields now display cleanly in WordPress revision comparisons with sub-field labels and values

= 2.1.3 - 2026-01-30 =
**Enhanced Repeater Widget Styling & Layout Improvements**

* **NEW:** Item Layout options for Grid/List - Stacked (Vertical), Side by Side (Horizontal), Image Right
* **NEW:** Vertical alignment control for horizontal item layouts
* **NEW:** Image Position option (Top/Bottom) for stacked layouts
* **NEW:** Image Style section with border radius, border, box shadow, object fit, height, and spacing controls
* **NEW:** Title Style section with typography, color, spacing, and alignment controls
* **NEW:** Content Style section with typography, color, and alignment controls
* **NEW:** Table Style section with header/cell colors, typography, borders, and padding
* **NEW:** Accordion Style section with header colors, typography, and icon styling
* **NEW:** Tabs Style section with tab colors, active states, and typography
* **FIXED:** Table layout now only shows columns that contain data (hides empty conditional columns)
* **FIXED:** Image field displaying attachment ID instead of actual image
* **FIXED:** Grid columns not respecting selected value (was always using default width)
* **REMOVED:** Duplicate "Cards" layout (same functionality as Grid)
* **IMPROVED:** Mobile responsive styles - horizontal layouts stack on smaller screens
* **IMPROVED:** Better image handling for ACF array, ID, and URL return formats

= 2.1.0 - 2026-01-29 =
**New Elementor Widgets & Repeater Field Fixes**

* **NEW:** ACF Enhanced Gallery Elementor widget with Grid, Masonry, Carousel, and Justified layouts
* **NEW:** ACF Enhanced Repeater Elementor widget with Grid, List, Cards, Accordion, Tabs, Timeline, and Table layouts
* **NEW:** Custom widget category "ACF Gallery & Repeater" in Elementor
* **NEW:** Preview Post ID setting for Elementor template editing (WooCommerce product templates support)
* **NEW:** Custom HTML template support for repeater widget with field placeholders ({{field_name}})
* **FIXED:** Enhanced Repeater sub-fields not persisting after saving in ACF Field Group editor
* **FIXED:** Select field values not showing after save in repeater rows
* **ADDED:** load_field, update_field, delete_field, and duplicate_field methods for proper sub-field handling
* **ADDED:** Proper post ID detection for Elementor and WooCommerce product templates
* **IMPROVED:** Better JavaScript support for ACF sub-field recognition
* **IMPROVED:** Normalized value structure for consistent sub-field key handling

= 2.0.1 - 2026-01-04 =
**Code Refactoring & WordPress.org Compliance**

* **REFACTORED:** Changed plugin acronym from ACFGRA to AGRFUXD across entire codebase
* **RENAMED:** All ACF/acf prefixed identifiers to AGRF/agrf to avoid common word prefix issue
* **RENAMED:** Field class files from `class-acf-field-enhanced-*` to `class-agrf-field-enhanced-*`
* **UPDATED:** All CSS classes, JavaScript references, and PHP function names
* **UPDATED:** Shortcode from `[acfgra_gallery]` to `[agrfuxd_gallery]`
* **UPDATED:** CSS custom properties from `--acfgra-*` to `--agrfuxd-*`
* **FIXED:** Installation folder path in readme.txt to match plugin slug

= 2.0.0 - 2025-12-30 =
** WordPress.org Text Domain Compliance**

* **CRITICAL:** Changed text domain from `acf-gallery-repeater-addon` to `advanced-gallery-repeater-fields-for-acf` to match plugin slug
* **UPDATED:** All 150+ translation strings across all files to use correct text domain
* **COMPLIANCE:** Plugin now fully compliant with WordPress.org text domain requirements
* **IMPORTANT:** If you have custom translations, you'll need to update your .po/.mo files to use the new text domain

= 1.9.9 - 2025-12-28 =
** Code Organization & Refactoring**

* **REFACTORED:** Main plugin file - separated code into logical files
* **CREATED:** `/admin/settings-page.php` - Settings page template file
* **CREATED:** `/includes/helper-functions.php` - Shortcodes and helper functions
* **CREATED:** `/includes/wpallimport-integration.php` - WP All Import compatibility
* **CREATED:** `/includes/elementor-integration.php` - Elementor compatibility
* **IMPROVED:** Main plugin file reduced by ~450 lines for better maintainability
* **IMPROVED:** Better code organization following WordPress best practices
* **IMPROVED:** Easier to navigate and maintain codebase
* **PERFORMANCE:** No functional changes - purely organizational improvements

= 1.9.8 - 2025-11-15 =
** WordPress.org Full Compliance**

* **FIXED:** Text domain mismatches - changed 'acf' to 'acf-gallery-repeater-addon' in all translation strings
* **ADDED:** Translator comments for all strings with placeholders
* **FIXED:** Ordered placeholders - changed %s to %1$s, %2$s for proper translation
* **ADDED:** phpcs:ignore comments for ACF's escaping functions (acf_esc_attrs, acf_esc_atts)
* **CREATED:** Languages folder for translation files
* **REMOVED:** load_plugin_textdomain() - WordPress.org handles translations automatically
* **ADDED:** phpcs:ignore comment for nonce verification (admin asset loading)
* **IMPROVED:** Full WordPress Coding Standards compliance

= 1.9.7 - 2025-10-15 =
** Lightbox Improvements**

* **FIXED:** Lightbox loader now hidden by default (only shows when loading images)
* **FIXED:** Thumbnail section now fixed at bottom - prevents moving down during navigation
* **ADDED:** Thumbnail click functionality - click any thumbnail to jump to that image
* **ADDED:** Active thumbnail highlighting during navigation
* **IMPROVED:** Removed all error_log() calls for WordPress.org compliance
* **IMPROVED:** Fixed escape output warnings - all variables properly escaped
* **UPDATED:** Changed "Tested up to" from 6.9.0 to 6.9 for WordPress.org requirements

= 1.9.6 - 2025-07-15 =
** Lightbox Navigation Fix**

* **FIXED:** Lightbox thumbnails moving below screen when navigating between images
* **FIXED:** Screen freeze when closing lightbox after using prev/next navigation
* **IMPROVED:** Only save scroll position on initial lightbox open, not during navigation
* **IMPROVED:** Better body style management during lightbox image transitions

= 1.9.5 - 2025-01-15 =
** Carousel & Lightbox Fixes**

* **FIXED:** Lightbox not showing - changed class from 'active' to 'is-open' to match CSS
* **FIXED:** Screen freeze on Escape key press - added proper keyboard event handling
* **CHANGED:** Carousel height now uses exact height instead of minimum height
* **IMPROVED:** Images in carousel now properly constrain to configured height using object-fit
* **IMPROVED:** Better image loading indication with fade-in effect in lightbox
* **IMPROVED:** Keyboard navigation now includes keyCode fallback for better browser compatibility

= 1.9.4 - 2025-01-15 =
** Carousel Height Control & Lightbox Fix**

* **ADDED:** Carousel height option in field settings (100-1000px with 50px steps)
* **FIXED:** Lightbox trigger positioning issue in carousel layout
* **IMPROVED:** Carousel now uses configured height as fallback instead of hardcoded 400px
* **IMPROVED:** Better CSS specificity for carousel lightbox triggers

= 1.9.3 - 2025-01-15 =
** Settings & Carousel Improvements**

* **REMOVED:** Repeater shortcode (use ACF's standard have_rows() and the_row() functions instead)
* **IMPROVED:** Settings page now only in Settings menu with plugin action link
* **ADDED:** Comprehensive gallery layouts documentation (Grid, Masonry, Carousel, Justified)
* **FIXED:** Carousel/slider image loading and display issues
* **IMPROVED:** Better image height calculation for carousel with fallback minimum height
* **IMPROVED:** Settings page now shows detailed information about each gallery layout
* **ADDED:** "Settings" link in plugins page for easy access

= 1.9.2 - 2025-01-15 =
** Elementor Integration Fix**

* **FIXED:** Elementor lightbox conflict - plugin's lightbox now works correctly in Elementor templates
* **FIXED:** Scroll freeze issue when closing lightbox in Elementor pages
* **IMPROVED:** Added `data-elementor-open-lightbox="no"` attribute to all gallery lightbox triggers
* **IMPROVED:** Better event propagation control to prevent Elementor from intercepting clicks
* **IMPROVED:** Proper scroll position restoration when closing lightbox

= 1.9.1 - 2025-01-15 =
** STABLE RELEASE - Repeater Field Perfected!**

* **CRITICAL FIX:** Resolved white screen issue caused by infinite recursion
* **MAJOR REFACTOR:** Simplified repeater to work WITH ACF's architecture instead of against it
* **CODE REDUCTION:** 500% simpler - reduced from 300+ lines to ~40 lines of code
* **PERFORMANCE:** Dramatically improved reliability and compatibility
* **WORKING:** Data now saves and loads correctly using ACF's built-in repeater logic
* **TESTED:** Fully functional with add/edit/delete/reorder operations
* **COMPATIBLE:** Works exactly like ACF Pro's repeater field
* **DEVELOPER:** Let ACF handle storage/loading automatically (no manual meta key management)

= 1.8.5 - 2025-01-14 =
* Fixed: Critical bug where old row count was fetched after updating
* Fixed: Old rows now properly deleted when row count decreases
* Improved: Comprehensive debugging added to trace data flow
* Updated: Better logging for troubleshooting save/load issues

= 1.8.4 - 2025-01-14 =
* Added: Comprehensive debugging to track repeater data save/load
* Fixed: Bug in delete_value method using wrong field property
* Improved: Enhanced error logging for troubleshooting

= 1.8.3 - 2025-01-13 =
* Fixed: Carousel height inconsistencies with equal height function
* Fixed: Carousel smooth sliding with improved CSS transitions
* Improved: Better carousel performance with hardware acceleration
* Updated: Modern settings page with gradient design and copy buttons

= 1.2.0 - 2024-12-XX =
**Major Update: ACF Free Compatibility!**

* **New:** Enhanced Repeater now works with ACF Free! Custom admin interface included
* **New:** Full repeater functionality without ACF Pro (add, remove, duplicate, reorder rows)
* **Improved:** Better admin UI with drag-and-drop row reordering
* **Improved:** Enhanced row actions (duplicate, remove with confirmation)
* **Improved:** Visual status bar showing row count and limits
* **Improved:** Clearer field instructions and help text
* **Updated:** Plugin name to "Advanced Gallery & Repeater Fields for ACF"
* **Updated:** Author information to UXD Experts
* **Updated:** Requires Plugins header for better WordPress 6.5+ compatibility
* **Fixed:** Better value loading and formatting for repeater fields
* **Fixed:** Improved validation for min/max row limits
* **Performance:** Optimized admin scripts and styles
* **Security:** Enhanced sanitization and escaping throughout

= 1.1.0 - 2024-XX-XX =
* Fixed: Gallery "Add Images" button now works correctly with WordPress Media Library
* Added: WP All Import Pro compatibility
* Added: Field type mapping for import/export
* Improved: Better admin interface for gallery field
* Improved: Sortable gallery attachments with drag & drop

= 1.0.0 - 2024-XX-XX =
* Initial release
* Enhanced Gallery field with Grid, Masonry, Carousel, and Justified layouts
* Enhanced Repeater field with List, Grid, Table, Accordion, Tabs, and Timeline layouts
* Built-in lightbox for galleries
* ARIA accessibility support
* Responsive layouts with CSS variables
* Touch and keyboard navigation

== Upgrade Notice ==

= 1.2.0 =
**Major update!** Enhanced Repeater now works with ACF Free - no ACF Pro required! Custom admin interface with full add/edit/reorder functionality. Highly recommended upgrade for all users.

= 1.1.0 =
Fixes gallery upload functionality and adds WP All Import compatibility. Recommended update.

== Developer Documentation ==

= Helper Functions =

**agrfuxd_render_enhanced_gallery( $field_name, $post_id )**
Manually render an Enhanced Gallery field with its configured layout.

Parameters:
* `$field_name` (string) - The field name or key
* `$post_id` (int|null) - Post ID (defaults to current post)

Returns: (string) HTML output

**agrfuxd_render_enhanced_repeater( $field_name, $post_id )**
Manually render an Enhanced Repeater field with its configured layout.

Parameters:
* `$field_name` (string) - The field name or key
* `$post_id` (int|null) - Post ID (defaults to current post)

Returns: (string) HTML output

= CSS Variables =

The plugin uses CSS custom properties for easy theming:

`
:root {
    /* Layout */
    --agrfuxd-columns: 3;
    --agrfuxd-gap: 20px;

    /* Colors */
    --agrfuxd-primary: #2271b1;
    --agrfuxd-text: #1e1e1e;
    --agrfuxd-border: #ddd;
    --agrfuxd-bg: #f9f9f9;

    /* Design */
    --agrfuxd-radius: 8px;
    --agrfuxd-shadow: 0 2px 8px rgba(0,0,0,0.1);

    /* Transitions */
    --agrfuxd-transition: 0.3s ease;
}
`

= CSS Classes =

* `.agrfuxd-gallery` - Gallery wrapper
* `.agrfuxd-gallery-grid` - Grid layout
* `.agrfuxd-gallery-masonry` - Masonry layout
* `.agrfuxd-gallery-carousel` - Carousel layout
* `.agrfuxd-repeater` - Repeater wrapper
* `.agrfuxd-repeater-grid` - Grid/cards layout
* `.agrfuxd-accordion` - Accordion layout
* `.agrfuxd-tabs` - Tabs layout
* `.agrfuxd-repeater-timeline` - Timeline layout

== Privacy Policy ==

This plugin does not collect, store, or transmit any user data. All content is stored locally in your WordPress database using standard ACF methods.

== Credits ==

Developed by [UXD Experts](https://uxdesignexperts.com) - WordPress experts specializing in user experience and custom development.
