<?php
/**
 * Plugin Name: Advanced Gallery & Repeater Fields for ACF
 * Plugin URI: https://uxdesignexperts.com/wordpress-plugins/advanced-gallery-repeater-fields-acf/
 * Description: Advanced ACF field types - Enhanced Gallery and Enhanced Repeater with built-in layouts (masonry, carousel, accordion, tabs, timeline). Works with ACF Free & Pro!
 * Version: 2.1.5
 * Author: UXD Experts
 * Author URI: https://uxdesignexperts.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-gallery-repeater-fields-for-acf
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: advanced-custom-fields
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AGRFUXD_VERSION', '2.1.5');
define('AGRFUXD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AGRFUXD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AGRFUXD_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class AGRFUXD_Gallery_Repeater_Addon {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('acf/include_field_types', array($this, 'register_field_types'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_page_styles'));
        add_filter('plugin_action_links_' . AGRFUXD_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));

        // WP All Import compatibility
        add_filter('wp_all_import_ace_postmeta_fields', array($this, 'wpai_ace_fields'), 10, 1);
        add_filter('pmxi_custom_field', array($this, 'wpai_import_field'), 10, 6);
        add_action('pmxi_saved_post', array($this, 'wpai_after_import'), 10, 1);

        // Load integrations
        $this->load_integrations();
    }

    /**
     * Load integration files
     */
    private function load_integrations() {
        require_once AGRFUXD_PLUGIN_DIR . 'includes/helper-functions.php';
        require_once AGRFUXD_PLUGIN_DIR . 'includes/wpallimport-integration.php';
        require_once AGRFUXD_PLUGIN_DIR . 'includes/elementor-integration.php';
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=agrfuxd-settings')) . '">' . __('Settings', 'advanced-gallery-repeater-fields-for-acf') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Register Custom ACF Field Types
     */
    public function register_field_types() {
        // Check if ACF is active
        if (!function_exists('acf_register_field_type')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
            return;
        }

        // Include field type classes
        require_once AGRFUXD_PLUGIN_DIR . 'includes/fields/class-agrf-field-enhanced-gallery.php';
        require_once AGRFUXD_PLUGIN_DIR . 'includes/fields/class-agrf-field-enhanced-repeater.php';

        // Register field types
        acf_register_field_type('AGRFUXD_Field_Enhanced_Gallery');
        acf_register_field_type('AGRFUXD_Field_Enhanced_Repeater');

        // Ensure ACF recognizes enhanced_repeater as supporting sub-fields
        add_filter('acf/get_field_types', array($this, 'mark_repeater_supports_sub_fields'));
    }

    /**
     * Mark enhanced_repeater field type as supporting sub-fields
     * This ensures ACF's field group editor shows the Sub Fields section
     */
    public function mark_repeater_supports_sub_fields($field_types) {
        if (isset($field_types['enhanced_repeater'])) {
            // Ensure supports array exists and includes sub_fields
            if (!isset($field_types['enhanced_repeater']->supports)) {
                $field_types['enhanced_repeater']->supports = array();
            }
            $field_types['enhanced_repeater']->supports['sub_fields'] = true;
        }
        return $field_types;
    }

    public function acf_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Advanced Gallery & Repeater Fields for ACF requires Advanced Custom Fields (Free or Pro) to be installed and activated.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
        </div>
        <?php
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        // Add under Settings menu only
        add_options_page(
            __('ACF Gallery & Repeater Settings', 'advanced-gallery-repeater-fields-for-acf'),
            __('ACF Gallery & Repeater', 'advanced-gallery-repeater-fields-for-acf'),
            'manage_options',
            'agrfuxd-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin styles and scripts for settings page
     */
    public function enqueue_settings_page_styles($hook) {
        if ($hook !== 'settings_page_agrfuxd-settings') {
            return;
        }

        wp_enqueue_style(
            'agrfuxd-settings-page',
            AGRFUXD_PLUGIN_URL . 'assets/css/settings-page.css',
            array(),
            AGRFUXD_VERSION
        );

        // Enqueue jQuery (required for copy button functionality)
        wp_enqueue_script('jquery');

        // Add inline script for copy button functionality
        $inline_script = "
        jQuery(document).ready(function($) {
            // Copy button functionality
            $('.agrfuxd-copy-btn').on('click', function() {
                var textToCopy = $(this).attr('data-copy');
                var \$btn = $(this);

                navigator.clipboard.writeText(textToCopy).then(function() {
                    var originalText = \$btn.html();
                    \$btn.html('<span class=\"dashicons dashicons-yes\"></span> Copied!');
                    \$btn.addClass('copied');

                    setTimeout(function() {
                        \$btn.html(originalText);
                        \$btn.removeClass('copied');
                    }, 2000);
                });
            });
        });
        ";

        wp_add_inline_script('jquery', $inline_script);
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include AGRFUXD_PLUGIN_DIR . 'admin/settings-page.php';
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'agrfuxd-frontend',
            AGRFUXD_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            AGRFUXD_VERSION
        );

        wp_enqueue_style(
            'agrfuxd-lightbox',
            AGRFUXD_PLUGIN_URL . 'assets/css/lightbox.css',
            array(),
            AGRFUXD_VERSION
        );

        wp_enqueue_script(
            'agrfuxd-frontend',
            AGRFUXD_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            AGRFUXD_VERSION,
            true
        );

        wp_enqueue_script('masonry');

        wp_localize_script('agrfuxd-frontend', 'agrfuxdSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('agrfuxd_nonce'),
        ));
    }

    public function enqueue_admin_assets($hook) {
        global $post_type;

        // Load on post edit screens and ACF field group pages
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking page parameter to enqueue assets
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if (in_array($hook, array('post.php', 'post-new.php'), true) ||
            ($page && strpos($page, 'acf') !== false)) {

            wp_enqueue_style(
                'agrfuxd-admin',
                AGRFUXD_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                AGRFUXD_VERSION
            );
        }
    }

    /**
     * WP All Import - Register fields for ACE editor support
     */
    public function wpai_ace_fields($fields) {
        $fields['enhanced_gallery'] = array(
            'name' => 'Enhanced Gallery',
            'type' => 'enhanced_gallery'
        );
        $fields['enhanced_repeater'] = array(
            'name' => 'Enhanced Repeater', 
            'type' => 'enhanced_repeater'
        );
        return $fields;
    }

    /**
     * WP All Import - Handle field import
     */
    public function wpai_import_field($value, $pid, $name, $existing_value, $import_id, $is_update) {
        // Get ACF field object
        $field = acf_get_field($name);
        
        if (!$field) {
            return $value;
        }
        
        // Handle Enhanced Gallery
        if ($field['type'] === 'enhanced_gallery') {
            return $this->process_gallery_import($value, $pid, $field);
        }
        
        // Handle Enhanced Repeater
        if ($field['type'] === 'enhanced_repeater') {
            return $this->process_repeater_import($value, $pid, $field);
        }
        
        return $value;
    }

    /**
     * Process gallery import - accepts comma-separated image IDs or URLs
     */
    private function process_gallery_import($value, $pid, $field) {
        if (empty($value)) {
            return array();
        }
        
        // If already an array, return as-is
        if (is_array($value)) {
            return array_map('intval', $value);
        }
        
        // Parse comma or pipe separated values
        $items = preg_split('/[,|]/', $value);
        $image_ids = array();
        
        foreach ($items as $item) {
            $item = trim($item);
            
            if (empty($item)) continue;
            
            // If numeric, treat as attachment ID
            if (is_numeric($item)) {
                $image_ids[] = intval($item);
            } else {
                // Treat as URL - try to find attachment ID
                $attachment_id = attachment_url_to_postid($item);
                if ($attachment_id) {
                    $image_ids[] = $attachment_id;
                }
            }
        }
        
        return $image_ids;
    }

    /**
     * Process repeater import - accepts serialized or JSON data
     */
    private function process_repeater_import($value, $pid, $field) {
        if (empty($value)) {
            return array();
        }
        
        // If already an array, return as-is
        if (is_array($value)) {
            return $value;
        }
        
        // Try JSON decode
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        
        // Try unserialize
        $unserialized = maybe_unserialize($value);
        if (is_array($unserialized)) {
            return $unserialized;
        }
        
        return $value;
    }

    /**
     * WP All Import - After import hook
     */
    public function wpai_after_import($pid) {
        // Clear any ACF caches for this post
        if (function_exists('acf_flush_value_cache')) {
            acf_flush_value_cache($pid);
        }
    }

}

// Initialize plugin
function agrfuxd_init() {
    return AGRFUXD_Gallery_Repeater_Addon::get_instance();
}
add_action('plugins_loaded', 'agrfuxd_init');

// Activation hook
register_activation_hook(__FILE__, 'agrfuxd_activate');
function agrfuxd_activate() {
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'agrfuxd_deactivate');
function agrfuxd_deactivate() {
    flush_rewrite_rules();
}
