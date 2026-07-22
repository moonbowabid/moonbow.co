<?php
/**
 * WP All Import Integration
 *
 * Adds support for importing Enhanced Gallery and Enhanced Repeater fields
 * via WP All Import Pro with the ACF Add-On
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Enhanced Gallery and Enhanced Repeater fields with WP All Import
 */
if (!function_exists('agrfuxd_wpallimport_addon_support')) {
    function agrfuxd_wpallimport_addon_support($supported_fields) {
        // Add our field types to supported list
        $supported_fields['enhanced_gallery'] = 'AGRFUXD_Field_Enhanced_Gallery';
        $supported_fields['enhanced_repeater'] = 'AGRFUXD_Field_Enhanced_Repeater';
        return $supported_fields;
    }
    add_filter('pmxi_acf_supported_fields', 'agrfuxd_wpallimport_addon_support', 10, 1);
}

/**
 * Map our custom field types to standard ACF types for WP All Import
 */
if (!function_exists('agrfuxd_pmxi_acf_field_type')) {
    function agrfuxd_pmxi_acf_field_type($field_type, $field) {
        if ($field['type'] === 'enhanced_gallery') {
            return 'gallery'; // Tell WP All Import to treat it like a gallery
        }
        if ($field['type'] === 'enhanced_repeater') {
            return 'repeater'; // Tell WP All Import to treat it like a repeater
        }
        return $field_type;
    }
    add_filter('pmxi_acf_field_type', 'agrfuxd_pmxi_acf_field_type', 10, 2);
}

/**
 * Tell WP All Import that our fields should be treated as gallery/repeater types
 */
add_filter('wp_all_import_is_acf_field_of_type', function($is_type, $type, $field) {
    if ($type === 'gallery' && isset($field['type']) && $field['type'] === 'enhanced_gallery') {
        return true;
    }
    if ($type === 'repeater' && isset($field['type']) && $field['type'] === 'enhanced_repeater') {
        return true;
    }
    return $is_type;
}, 10, 3);
