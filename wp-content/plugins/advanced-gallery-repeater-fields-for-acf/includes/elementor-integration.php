<?php
/**
 * Elementor Integration
 *
 * Adds support for Enhanced Gallery and Enhanced Repeater fields in Elementor
 * Includes custom Elementor widgets for displaying ACF data
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AGRFUXD Elementor Integration Class
 */
class AGRFUXD_Elementor_Integration {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);

        // Register widget category
        add_action('elementor/elements/categories_registered', [$this, 'add_widget_category']);

        // Enqueue widget styles
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_widget_styles']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_widget_styles']);

        // Enqueue widget scripts
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_widget_scripts']);

        // ACF field type mappings
        add_filter('elementor/acf/field_groups', [$this, 'map_acf_field_groups'], 10, 1);
        add_filter('elementor/widgets/acf/fields', [$this, 'map_acf_fields'], 10, 1);
    }

    /**
     * Register Elementor widgets
     */
    public function register_widgets($widgets_manager) {
        // Check if ACF is active
        if (!function_exists('get_field')) {
            return;
        }

        // Include widget files
        require_once AGRFUXD_PLUGIN_DIR . 'includes/elementor/widgets/class-enhanced-gallery-widget.php';
        require_once AGRFUXD_PLUGIN_DIR . 'includes/elementor/widgets/class-enhanced-repeater-widget.php';

        // Register widgets
        $widgets_manager->register(new AGRFUXD_Enhanced_Gallery_Widget());
        $widgets_manager->register(new AGRFUXD_Enhanced_Repeater_Widget());
    }

    /**
     * Add widget category
     */
    public function add_widget_category($elements_manager) {
        $elements_manager->add_category(
            'agrfuxd',
            [
                'title' => __('ACF Gallery & Repeater', 'advanced-gallery-repeater-fields-for-acf'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Enqueue widget styles
     */
    public function enqueue_widget_styles() {
        wp_enqueue_style(
            'agrfuxd-elementor-widgets',
            AGRFUXD_PLUGIN_URL . 'assets/css/elementor-widgets.css',
            ['agrfuxd-frontend'],
            AGRFUXD_VERSION
        );
    }

    /**
     * Enqueue widget scripts
     */
    public function enqueue_widget_scripts() {
        wp_enqueue_script(
            'agrfuxd-elementor-widgets',
            AGRFUXD_PLUGIN_URL . 'assets/js/elementor-widgets.js',
            ['jquery', 'agrfuxd-frontend'],
            AGRFUXD_VERSION,
            true
        );
    }

    /**
     * Map ACF field groups for Elementor
     */
    public function map_acf_field_groups($field_groups) {
        if (is_array($field_groups)) {
            foreach ($field_groups as &$group) {
                if (isset($group['fields']) && is_array($group['fields'])) {
                    foreach ($group['fields'] as &$field) {
                        if (isset($field['type']) && $field['type'] === 'enhanced_gallery') {
                            // Tell Elementor this is a gallery field
                            $field['elementor_type'] = 'gallery';
                        }
                        if (isset($field['type']) && $field['type'] === 'enhanced_repeater') {
                            // Tell Elementor this is a repeater field
                            $field['elementor_type'] = 'repeater';
                        }
                    }
                }
            }
        }
        return $field_groups;
    }

    /**
     * Map ACF fields for Elementor widgets
     */
    public function map_acf_fields($fields) {
        if (!is_array($fields)) {
            $fields = [];
        }

        // Add our field types with proper categorization
        $fields['enhanced_gallery'] = [
            'type' => 'gallery',
            'widget' => 'gallery',
        ];

        $fields['enhanced_repeater'] = [
            'type' => 'repeater',
            'widget' => 'repeater',
        ];

        return $fields;
    }
}

// Initialize the integration
add_action('elementor/init', function() {
    AGRFUXD_Elementor_Integration::get_instance();
});

// Legacy filters for backward compatibility
add_filter('elementor/acf/field_groups', function($field_groups) {
    if (is_array($field_groups)) {
        foreach ($field_groups as &$group) {
            if (isset($group['fields']) && is_array($group['fields'])) {
                foreach ($group['fields'] as &$field) {
                    if (isset($field['type']) && $field['type'] === 'enhanced_gallery') {
                        $field['elementor_type'] = 'gallery';
                    }
                    if (isset($field['type']) && $field['type'] === 'enhanced_repeater') {
                        $field['elementor_type'] = 'repeater';
                    }
                }
            }
        }
    }
    return $field_groups;
}, 10, 1);

add_filter('elementor/widgets/acf/fields', function($fields) {
    if (!is_array($fields)) {
        $fields = [];
    }

    $fields['enhanced_gallery'] = [
        'type' => 'gallery',
        'widget' => 'gallery',
    ];

    $fields['enhanced_repeater'] = [
        'type' => 'repeater',
        'widget' => 'repeater',
    ];

    return $fields;
}, 10, 1);
