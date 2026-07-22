<?php
/**
 * Enhanced Repeater Field Type
 *
 * Custom ACF field type that extends repeater with layout options
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AGRFUXD_Field_Enhanced_Repeater extends acf_field {

    /**
     * ACF checks this to determine if field supports sub-fields in the field group editor
     */
    public $supports = array('sub_fields' => true);

    /**
     * Initialize the field type
     */
    public function initialize() {
        $this->name = 'enhanced_repeater';
        $this->label = __('Enhanced Repeater', 'advanced-gallery-repeater-fields-for-acf');
        $this->category = 'layout';
        $this->description = __('Add rows of repeating fields. Works with ACF Free!', 'advanced-gallery-repeater-fields-for-acf');

        // Localization
        $this->l10n = array(
            'min' => __('Minimum rows reached ({min} rows)', 'advanced-gallery-repeater-fields-for-acf'),
            'max' => __('Maximum rows reached ({max} rows)', 'advanced-gallery-repeater-fields-for-acf'),
        );

        // Default field values
        $this->defaults = array(
            'sub_fields' => array(),
            'min' => 0,
            'max' => 0,
            'layout' => 'table',
            'button_label' => __('Add Row', 'advanced-gallery-repeater-fields-for-acf'),
        );

        // Register revision field filters to handle repeater data in post revisions
        add_filter('_wp_post_revision_fields', array($this, 'register_revision_field_filters'), 20, 2);
    }

    /**
     * Enqueue scripts for field group admin
     * This ensures ACF's field group JavaScript recognizes our field supports sub-fields
     */
    public function field_group_admin_enqueue_scripts() {
        // Ensure ACF's field group script is loaded
        wp_enqueue_script('acf-field-group');

        // Tell ACF's JavaScript that this field supports sub-fields
        $inline_script = "
        (function($) {
            if (typeof acf === 'undefined') return;

            // Wait for ACF to be fully ready
            acf.addAction('ready', function() {
                // Register field type support for sub-fields
                if (acf.getFieldType) {
                    var fieldType = acf.getFieldType('enhanced_repeater');
                    if (fieldType) {
                        fieldType.supports = fieldType.supports || {};
                        fieldType.supports.sub_fields = true;
                    }
                }

                // Also add to the field types data if it exists
                if (acf.data && acf.data.fieldTypes) {
                    acf.data.fieldTypes['enhanced_repeater'] = acf.data.fieldTypes['enhanced_repeater'] || {};
                    acf.data.fieldTypes['enhanced_repeater'].supports = acf.data.fieldTypes['enhanced_repeater'].supports || {};
                    acf.data.fieldTypes['enhanced_repeater'].supports.sub_fields = true;
                }
            });

            // Ensure sub-fields are included when saving the field group
            acf.addFilter('prepare_field_for_save', function(field) {
                if (field.type === 'enhanced_repeater') {
                    // Ensure sub_fields property exists
                    field.sub_fields = field.sub_fields || [];
                }
                return field;
            });
        })(jQuery);
        ";

        wp_add_inline_script('acf-field-group', $inline_script);
    }

    /**
     * Enqueue scripts and styles
     * Ensure ACF's repeater functionality is available
     */
    public function input_admin_enqueue_scripts() {
        // Enqueue ACF's core scripts
        wp_enqueue_script('acf-input');
        wp_enqueue_style('acf-input');

        // Enqueue jQuery UI for drag and drop
        wp_enqueue_script('jquery-ui-sortable');

        // Enqueue our custom repeater JavaScript
        wp_enqueue_script(
            'agrfuxd-repeater-field',
            AGRFUXD_PLUGIN_URL . 'assets/js/repeater-field.js',
            array('jquery', 'jquery-ui-sortable', 'acf-input'),
            AGRFUXD_VERSION,
            true
        );

        // Enqueue repeater CSS for styling
        wp_enqueue_style(
            'agrfuxd-repeater-field',
            AGRFUXD_PLUGIN_URL . 'assets/css/repeater-field.css',
            array('acf-input'),
            AGRFUXD_VERSION
        );
    }

    /**
     * Render field settings in ACF admin
     * This is the KEY method that shows the Sub Fields UI!
     */
    public function render_field_settings($field) {
        // Ensure sub_fields is an array
        $sub_fields = isset($field['sub_fields']) && is_array($field['sub_fields']) ? $field['sub_fields'] : array();

        // Get the parent ID - use ID if available, fall back to key for new fields
        $parent = 0;
        if (!empty($field['ID'])) {
            $parent = $field['ID'];
        } elseif (!empty($field['key'])) {
            $parent = $field['key'];
        }

        // Sub Fields section - CRITICAL: This shows the "+ Add Field" UI
        $args = array(
            'fields' => $sub_fields,
            'parent' => $parent,
        );
        ?>
        <div class="acf-field acf-field-setting-sub_fields" data-setting="repeater" data-name="sub_fields">
            <div class="acf-label">
                <label><?php esc_html_e('Sub Fields', 'advanced-gallery-repeater-fields-for-acf'); ?></label>
                <p class="description"></p>
            </div>
            <div class="acf-input acf-input-sub">
                <?php
                acf_get_view('acf-field-group/fields', $args);
                ?>
            </div>
        </div>
        <?php

        // Layout
        acf_render_field_setting($field, array(
            'label' => __('Layout', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => '',
            'type' => 'radio',
            'name' => 'layout',
            'layout' => 'horizontal',
            'choices' => array(
                'table' => __('Table', 'advanced-gallery-repeater-fields-for-acf'),
                'block' => __('Block', 'advanced-gallery-repeater-fields-for-acf'),
                'row' => __('Row', 'advanced-gallery-repeater-fields-for-acf'),
            ),
        ));

        // Minimum Rows
        acf_render_field_setting($field, array(
            'label' => __('Minimum Rows', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => '',
            'type' => 'number',
            'name' => 'min',
            'placeholder' => '0',
        ));

        // Maximum Rows
        acf_render_field_setting($field, array(
            'label' => __('Maximum Rows', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => '',
            'type' => 'number',
            'name' => 'max',
            'placeholder' => '0',
        ));

        // Button Label
        acf_render_field_setting($field, array(
            'label' => __('Button Label', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => '',
            'type' => 'text',
            'name' => 'button_label',
            'placeholder' => __('Add Row', 'advanced-gallery-repeater-fields-for-acf'),
        ));
    }


    /**
     * Load field configuration (for Field Group editor)
     * This ensures sub-fields are loaded when editing the field group
     */
    public function load_field($field) {
        // Ensure sub_fields is set
        if (!isset($field['sub_fields'])) {
            $field['sub_fields'] = array();
        }

        // Load sub-fields if we have a valid field key
        if (!empty($field['key'])) {
            $sub_fields = acf_get_fields($field);
            if (is_array($sub_fields)) {
                $field['sub_fields'] = $sub_fields;
            }
        }

        return $field;
    }

    /**
     * Update field configuration (when saving Field Group)
     * This ensures sub-fields are properly saved with the parent field
     */
    public function update_field($field) {
        // Ensure sub_fields array exists
        if (!isset($field['sub_fields'])) {
            $field['sub_fields'] = array();
        }

        return $field;
    }

    /**
     * Delete field (when removing from Field Group)
     * This cleans up sub-fields when the parent field is deleted
     */
    public function delete_field($field) {
        // Get sub-fields
        if (!empty($field['sub_fields'])) {
            foreach ($field['sub_fields'] as $sub_field) {
                acf_delete_field($sub_field['ID']);
            }
        }
    }

    /**
     * Duplicate field (when duplicating in Field Group)
     * This properly duplicates sub-fields when the parent field is duplicated
     */
    public function duplicate_field($field) {
        // Duplicate sub-fields if they exist
        if (!empty($field['sub_fields'])) {
            $new_sub_fields = array();
            foreach ($field['sub_fields'] as $sub_field) {
                $new_sub_field = acf_duplicate_field($sub_field['ID'], $field['ID']);
                if ($new_sub_field) {
                    $new_sub_fields[] = $new_sub_field;
                }
            }
            $field['sub_fields'] = $new_sub_fields;
        }

        return $field;
    }

    /**
     * Prepare field for input
     */
    public function prepare_field($field) {
        // Ensure field key exists
        if (empty($field['key'])) {
            return $field;
        }

        // Ensure sub_fields are loaded
        if (empty($field['sub_fields'])) {
            $field['sub_fields'] = acf_get_fields($field);
        }

        // Ensure value is loaded
        if (!isset($field['value'])) {
            $field['value'] = array();
        }

        return $field;
    }

    /**
     * Render the field input in admin
     */
    public function render_field($field) {
        // Get sub fields (let ACF handle loading)
        $sub_fields = acf_get_fields($field);

        // Check for sub-fields
        if (empty($sub_fields)) {
            echo '<p style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">';
            esc_html_e('Please add sub-fields to this repeater by clicking "+ Add Field" below this field in the field group editor.', 'advanced-gallery-repeater-fields-for-acf');
            echo '</p>';
            return;
        }

        // Get value
        $value = $field['value'];
        if (!is_array($value)) {
            $value = array();
        }

        // Apply min rows
        if ($field['min'] && count($value) < $field['min']) {
            for ($i = count($value); $i < $field['min']; $i++) {
                $value[] = array();
            }
        }

        // Setup vars
        $layout = $field['layout'];
        $button_label = $field['button_label'];

        // Wrapper attributes
        $div = array(
            'class' => 'acf-repeater',
            'data-min' => $field['min'],
            'data-max' => $field['max'],
        );
        ?>
        <div <?php echo acf_esc_attrs($div); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- acf_esc_attrs is a safe escaping function ?> data-type="repeater">
            <table class="acf-table <?php echo esc_attr($layout === 'table' ? '-clear' : ''); ?>">

                <?php if ($layout === 'table') : ?>
                <thead>
                    <tr>
                        <th class="acf-row-handle"></th>
                        <?php foreach ($sub_fields as $sub_field) :
                            $atts = array(
                                'class' => 'acf-th',
                                'data-name' => $sub_field['_name'],
                                'data-type' => $sub_field['type'],
                                'data-key' => $sub_field['key'],
                            );
                            if ($sub_field['required']) {
                                $atts['data-required'] = '1';
                            }
                        ?>
                        <th <?php echo acf_esc_attrs($atts); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- acf_esc_attrs is a safe escaping function ?>>
                            <?php acf_render_field_label($sub_field); ?>
                            <?php if (isset($sub_field['instructions'])) : ?>
                                <p class="description"><?php echo esc_html($sub_field['instructions']); ?></p>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                        <th class="acf-row-handle"></th>
                    </tr>
                </thead>
                <?php endif; ?>

                <tbody>
                    <?php if ($value) : ?>
                        <?php foreach ($value as $i => $row) : ?>
                            <?php $this->render_row($field, $sub_fields, $i, $row); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="acf-actions">
                <a href="#" class="acf-button button button-primary" data-event="add-row"><?php echo esc_html($button_label); ?></a>
            </div>

            <script type="text/html" class="tmpl-row">
                <?php $this->render_row($field, $sub_fields, 'acfcloneindex', array()); ?>
            </script>
        </div>
        <?php
    }

    /**
     * Render a single repeater row
     */
    protected function render_row($field, $sub_fields, $i, $value) {
        $attrs = array(
            'class' => 'acf-row',
            'data-id' => $i,
        );

        if ($field['layout'] === 'row') {
            $attrs['class'] .= ' acf-row-layout-row';
        } elseif ($field['layout'] === 'block') {
            $attrs['class'] .= ' acf-row-layout-block';
        }
        ?>
        <tr <?php echo acf_esc_attrs($attrs); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- acf_esc_attrs is a safe escaping function ?>>
            <td class="acf-row-handle order" title="<?php esc_attr_e('Drag to reorder', 'advanced-gallery-repeater-fields-for-acf'); ?>">
                <a class="acf-icon -collapse" href="#" title="<?php esc_attr_e('Click to toggle', 'advanced-gallery-repeater-fields-for-acf'); ?>"></a>
            </td>

            <?php foreach ($sub_fields as $sub_field) :
                // Use cleaner prefix approach (like ACF Pro does)
                $sub_field['prefix'] = $field['name'] . '[' . $i . ']';

                // Try multiple possible keys for the sub-field value
                // ACF may store/retrieve values using key, _name, or name depending on context
                $sub_field_value = null;
                if (is_array($value)) {
                    if (isset($value[$sub_field['key']])) {
                        $sub_field_value = $value[$sub_field['key']];
                    } elseif (isset($value[$sub_field['_name']])) {
                        $sub_field_value = $value[$sub_field['_name']];
                    } elseif (isset($value[$sub_field['name']])) {
                        $sub_field_value = $value[$sub_field['name']];
                    }
                }
                $sub_field['value'] = $sub_field_value;

                // Render field
                acf_render_field_wrap($sub_field, 'td');
            endforeach; ?>

            <td class="acf-row-handle remove">
                <a class="acf-icon -minus small" href="#" data-event="remove-row" title="<?php esc_attr_e('Remove row', 'advanced-gallery-repeater-fields-for-acf'); ?>"></a>
                <a class="acf-icon -plus small" href="#" data-event="duplicate-row" title="<?php esc_attr_e('Duplicate row', 'advanced-gallery-repeater-fields-for-acf'); ?>"></a>
            </td>
        </tr>
        <?php
    }

    /**
     * Format value for frontend
     */
    public function format_value($value, $post_id, $field) {
        // Return empty array if no value
        if (empty($value) || !is_array($value)) {
            return array();
        }

        // Let ACF handle sub-field formatting
        return $value;
    }

    /**
     * Validate value
     */
    public function validate_value($valid, $value, $field, $input) {
        // Check min/max rows
        if (!is_array($value)) {
            $value = array();
        }

        $count = count($value);

        if ($field['min'] && $count < $field['min']) {
            /* translators: %d: minimum number of rows */
            $valid = sprintf(__('Minimum rows reached (%d rows)', 'advanced-gallery-repeater-fields-for-acf'), $field['min']);
            return $valid;
        }

        if ($field['max'] && $count > $field['max']) {
            /* translators: %d: maximum number of rows */
            $valid = sprintf(__('Maximum rows exceeded (%d rows)', 'advanced-gallery-repeater-fields-for-acf'), $field['max']);
            return $valid;
        }

        return $valid;
    }

    /**
     * Update value before saving
     *
     * SIMPLIFIED: Let ACF handle the storage instead of doing it manually
     */
    public function update_value($value, $post_id, $field) {
        // Return null if empty
        if (empty($value) || !is_array($value)) {
            return null;
        }

        // Remove completely empty rows (optional - keeps data clean)
        $value = array_filter($value, function($row) {
            if (!is_array($row)) {
                return false;
            }
            // Check if row has any non-empty values
            foreach ($row as $val) {
                if (!empty($val) || $val === '0' || $val === 0) {
                    return true;
                }
            }
            return false;
        });

        // Reset array indexes to 0, 1, 2... (required for ACF)
        $value = array_values($value);

        return $value;
    }

    /**
     * Load value from database
     */
    public function load_value($value, $post_id, $field) {
        // Handle serialized data
        if (is_string($value) && !empty($value)) {
            $value = maybe_unserialize($value);
        }

        // Ensure we have an array
        if (!is_array($value)) {
            return array();
        }

        // Get sub-fields for key mapping
        $sub_fields = acf_get_fields($field);

        if (empty($sub_fields)) {
            return $value;
        }

        // Normalize the value structure - ensure consistent key usage
        $normalized_value = array();
        foreach ($value as $row_index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized_row = array();
            foreach ($sub_fields as $sub_field) {
                $sub_field_value = null;

                // Try to find the value using different possible keys
                if (isset($row[$sub_field['key']])) {
                    $sub_field_value = $row[$sub_field['key']];
                } elseif (isset($row[$sub_field['_name']])) {
                    $sub_field_value = $row[$sub_field['_name']];
                } elseif (isset($row[$sub_field['name']])) {
                    $sub_field_value = $row[$sub_field['name']];
                }

                // Store using _name for consistency (this is what ACF uses in form inputs)
                $normalized_row[$sub_field['_name']] = $sub_field_value;
            }

            $normalized_value[$row_index] = $normalized_row;
        }

        return $normalized_value;
    }

    /**
     * Register revision field filters for enhanced repeater fields
     *
     * Hooks into _wp_post_revision_fields to detect enhanced_repeater fields
     * and add custom formatting filters before ACF processes them
     */
    public function register_revision_field_filters($fields, $post) {
        $post_id = is_object($post) ? $post->ID : (is_array($post) ? $post['ID'] : 0);
        if (!$post_id) {
            return $fields;
        }

        $meta = get_post_meta($post_id);
        foreach ($meta as $name => $value) {
            if (strpos($name, '_') === 0) {
                continue;
            }
            $key = isset($meta['_' . $name]) ? $meta['_' . $name][0] : null;
            if (!$key) {
                continue;
            }
            $field = acf_get_field($key);
            if (!$field || $field['type'] !== 'enhanced_repeater') {
                continue;
            }
            add_filter("_wp_post_revision_field_{$name}", array($this, 'format_repeater_revision_value'), 9, 4);
        }
        return $fields;
    }

    /**
     * Format repeater revision value for display
     *
     * Converts nested array data into a readable string format
     * to prevent "Array to string conversion" warnings in revision history
     */
    public function format_repeater_revision_value($value, $field_name, $post, $direction) {
        $raw = maybe_unserialize($value);
        if (!is_array($raw)) {
            return $value;
        }

        $label_map = array();
        $meta_key = get_post_meta($post->ID, '_' . $field_name, true);
        if ($meta_key) {
            $field = acf_get_field($meta_key);
            if ($field && !empty($field['sub_fields'])) {
                foreach ($field['sub_fields'] as $sub) {
                    $label_map[$sub['name']] = $sub['label'];
                    $label_map[$sub['key']] = $sub['label'];
                }
            }
        }

        $lines = array();
        foreach ($raw as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $cells = array();
            foreach ($row as $key => $val) {
                if (is_array($val)) {
                    $val = implode(', ', array_map('strval', $val));
                }
                $label = isset($label_map[$key]) ? $label_map[$key] : $key;
                $cells[] = $label . ': ' . $val;
            }
            $lines[] = 'Row ' . ($i + 1) . ' [' . implode(' | ', $cells) . ']';
        }
        return implode("\n", $lines);
    }
}
