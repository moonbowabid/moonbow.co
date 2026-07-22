<?php
/**
 * Enhanced Gallery Field Type
 *
 * Custom ACF field type that extends gallery with layout options
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AGRFUXD_Field_Enhanced_Gallery extends acf_field {

    /**
     * Initialize the field type
     */
    public function initialize() {
        $this->name = 'enhanced_gallery';
        $this->label = __('Enhanced Gallery', 'advanced-gallery-repeater-fields-for-acf');
        $this->category = 'content';
        $this->description = __('Gallery field with built-in layout options (grid, masonry, carousel, lightbox)', 'advanced-gallery-repeater-fields-for-acf');
        $this->defaults = array(
            'return_format' => 'array',
            'library' => 'all',
            'insert' => 'append',
            'min' => 0,
            'max' => 0,
            'min_width' => 0,
            'min_height' => 0,
            'min_size' => 0,
            'max_width' => 0,
            'max_height' => 0,
            'max_size' => 0,
            'mime_types' => '',
            'preview_size' => 'medium',
            // Layout options
            'display_layout' => 'grid',
            'columns' => 3,
            'gap' => 15,
            'image_size' => 'medium',
            'enable_lightbox' => 1,
            'show_caption' => 0,
            'enable_lazy_load' => 1,
            // Carousel options
            'carousel_autoplay' => 1,
            'carousel_speed' => 3000,
            'carousel_slides_to_show' => 3,
            'carousel_height' => 400,
            'carousel_dots' => 1,
            'carousel_arrows' => 1,
        );
        
        // Add AJAX actions for media
        add_action('wp_ajax_agrfuxd_get_attachment', array($this, 'ajax_get_attachment'));
    }

    /**
     * Enqueue scripts and styles for the field
     */
    public function input_admin_enqueue_scripts() {
        // Enqueue media
        wp_enqueue_media();
        
        // Enqueue ACF's gallery scripts if available
        if (function_exists('acf_enqueue_uploader')) {
            acf_enqueue_uploader();
        }
        
        // Enqueue sortable
        wp_enqueue_script('jquery-ui-sortable');
        
        // Enqueue our custom admin JS
        wp_enqueue_script(
            'agrfuxd-gallery-field',
            AGRFUXD_PLUGIN_URL . 'assets/js/gallery-field.js',
            array('jquery', 'jquery-ui-sortable'),
            AGRFUXD_VERSION,
            true
        );

        wp_localize_script('agrfuxd-gallery-field', 'agrfuxd_gallery', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('agrfuxd_gallery_nonce'),
            'text' => array(
                'add_images' => __('Add Images to Gallery', 'advanced-gallery-repeater-fields-for-acf'),
                'select_images' => __('Select Images', 'advanced-gallery-repeater-fields-for-acf'),
                'edit' => __('Edit', 'advanced-gallery-repeater-fields-for-acf'),
                'remove' => __('Remove', 'advanced-gallery-repeater-fields-for-acf'),
            )
        ));

        // Enqueue admin CSS
        wp_enqueue_style(
            'agrfuxd-gallery-field',
            AGRFUXD_PLUGIN_URL . 'assets/css/gallery-field.css',
            array(),
            AGRFUXD_VERSION
        );
    }

    /**
     * Render field settings in ACF admin
     */
    public function render_field_settings($field) {
        
        // Layout Section Header
        acf_render_field_setting($field, array(
            'label' => __('Display Layout', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Choose how the gallery will be displayed on frontend', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'select',
            'name' => 'display_layout',
            'choices' => array(
                'grid' => __('Grid', 'advanced-gallery-repeater-fields-for-acf'),
                'masonry' => __('Masonry', 'advanced-gallery-repeater-fields-for-acf'),
                'carousel' => __('Carousel / Slider', 'advanced-gallery-repeater-fields-for-acf'),
                'justified' => __('Justified', 'advanced-gallery-repeater-fields-for-acf'),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Columns', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Number of columns for grid/masonry layout', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'columns',
            'min' => 1,
            'max' => 6,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '!=',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Gap (px)', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Space between images in pixels', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'gap',
            'min' => 0,
            'max' => 50,
        ));

        acf_render_field_setting($field, array(
            'label' => __('Image Size', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Select the image size to display', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'select',
            'name' => 'image_size',
            'choices' => $this->get_image_sizes(),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Enable Lightbox', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Open images in a lightbox when clicked', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'true_false',
            'name' => 'enable_lightbox',
            'ui' => 1,
        ));

        acf_render_field_setting($field, array(
            'label' => __('Show Captions', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Display image captions below each image', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'true_false',
            'name' => 'show_caption',
            'ui' => 1,
        ));

        acf_render_field_setting($field, array(
            'label' => __('Lazy Loading', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Enable lazy loading for better performance', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'true_false',
            'name' => 'enable_lazy_load',
            'ui' => 1,
        ));

        // Carousel Settings
        acf_render_field_setting($field, array(
            'label' => __('Carousel: Autoplay', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'true_false',
            'name' => 'carousel_autoplay',
            'ui' => 1,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '==',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Carousel: Speed (ms)', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Autoplay speed in milliseconds', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'carousel_speed',
            'min' => 1000,
            'max' => 10000,
            'step' => 500,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '==',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Carousel: Slides to Show', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'carousel_slides_to_show',
            'min' => 1,
            'max' => 6,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '==',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Carousel: Height (px)', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Set minimum height for carousel slides', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'carousel_height',
            'min' => 100,
            'max' => 1000,
            'step' => 50,
            'default_value' => 400,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '==',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Carousel: Show Dots', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'true_false',
            'name' => 'carousel_dots',
            'ui' => 1,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '==',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Carousel: Show Arrows', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'true_false',
            'name' => 'carousel_arrows',
            'ui' => 1,
            'conditions' => array(
                array(
                    array(
                        'field' => 'display_layout',
                        'operator' => '==',
                        'value' => 'carousel',
                    ),
                ),
            ),
        ));

        // Standard Gallery Settings
        acf_render_field_setting($field, array(
            'label' => __('Return Format', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Specify the returned value on front end', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'radio',
            'name' => 'return_format',
            'layout' => 'horizontal',
            'choices' => array(
                'array' => __('Image Array', 'advanced-gallery-repeater-fields-for-acf'),
                'url' => __('Image URL', 'advanced-gallery-repeater-fields-for-acf'),
                'id' => __('Image ID', 'advanced-gallery-repeater-fields-for-acf'),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Library', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Limit the media library choice', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'radio',
            'name' => 'library',
            'layout' => 'horizontal',
            'choices' => array(
                'all' => __('All', 'advanced-gallery-repeater-fields-for-acf'),
                'uploadedTo' => __('Uploaded to post', 'advanced-gallery-repeater-fields-for-acf'),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Minimum Selection', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Minimum number of images required', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'min',
            'min' => 0,
        ));

        acf_render_field_setting($field, array(
            'label' => __('Maximum Selection', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Maximum number of images allowed (0 = unlimited)', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'number',
            'name' => 'max',
            'min' => 0,
        ));

        acf_render_field_setting($field, array(
            'label' => __('Insert', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Specify where new images are added', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'select',
            'name' => 'insert',
            'choices' => array(
                'append' => __('Append to the end', 'advanced-gallery-repeater-fields-for-acf'),
                'prepend' => __('Prepend to the beginning', 'advanced-gallery-repeater-fields-for-acf'),
            ),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Preview Size', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Image size shown in admin', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'select',
            'name' => 'preview_size',
            'choices' => $this->get_image_sizes(),
        ));

        acf_render_field_setting($field, array(
            'label' => __('Allowed File Types', 'advanced-gallery-repeater-fields-for-acf'),
            'instructions' => __('Comma separated list. Leave blank for all types', 'advanced-gallery-repeater-fields-for-acf'),
            'type' => 'text',
            'name' => 'mime_types',
            'placeholder' => 'jpg, jpeg, png, gif, webp',
        ));
    }

    /**
     * Get available image sizes
     */
    private function get_image_sizes() {
        $sizes = array();
        $registered_sizes = get_intermediate_image_sizes();
        
        foreach ($registered_sizes as $size) {
            $sizes[$size] = ucwords(str_replace(array('-', '_'), ' ', $size));
        }
        
        $sizes['full'] = __('Full Size', 'advanced-gallery-repeater-fields-for-acf');
        
        return $sizes;
    }

    /**
     * Render the field input in admin
     */
    public function render_field($field) {
        // Get current value (array of attachment IDs)
        $value = $field['value'];
        
        if (!is_array($value)) {
            $value = array();
        }
        
        // Ensure value contains only valid IDs
        $value = array_filter(array_map('intval', $value));
        
        // Settings
        $preview_size = $field['preview_size'] ?? 'medium';
        $library = $field['library'] ?? 'all';
        $min = $field['min'] ?? 0;
        $max = $field['max'] ?? 0;
        
        // Create attributes for the wrapper
        $wrapper_atts = array(
            'class' => 'agrfuxd-gallery-field',
            'data-min' => $min,
            'data-max' => $max,
            'data-library' => $library,
            'data-preview-size' => $preview_size,
            'data-insert' => $field['insert'] ?? 'append',
            'data-mime-types' => $field['mime_types'] ?? '',
        );
        
        ?>
        <div <?php echo acf_esc_atts($wrapper_atts); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- acf_esc_atts is a safe escaping function ?>>

            <input type="hidden" name="<?php echo esc_attr($field['name']); ?>" value="" />

            <div class="agrfuxd-gallery-attachments">
                <?php 
                if (!empty($value)) {
                    foreach ($value as $attachment_id) {
                        $this->render_attachment($attachment_id, $field);
                    }
                }
                ?>
            </div>
            
            <div class="agrfuxd-gallery-toolbar">
                <button type="button" class="agrfuxd-add-images button button-primary">
                    <?php esc_html_e('Add Images', 'advanced-gallery-repeater-fields-for-acf'); ?>
                </button>

                <?php if (!empty($value)) : ?>
                <button type="button" class="agrfuxd-remove-all button" style="margin-left: 5px;">
                    <?php esc_html_e('Remove All', 'advanced-gallery-repeater-fields-for-acf'); ?>
                </button>
                <?php endif; ?>
            </div>

            <p class="agrfuxd-gallery-hint description">
                <?php 
                $layout_labels = array(
                    'grid' => __('Grid', 'advanced-gallery-repeater-fields-for-acf'),
                    'masonry' => __('Masonry', 'advanced-gallery-repeater-fields-for-acf'),
                    'carousel' => __('Carousel', 'advanced-gallery-repeater-fields-for-acf'),
                    'justified' => __('Justified', 'advanced-gallery-repeater-fields-for-acf'),
                );
                $display_layout = $field['display_layout'] ?? 'grid';
                printf(
                    /* translators: 1: Layout name, 2: Lightbox status (Enabled/Disabled) */
                    esc_html__('Frontend Layout: %1$s | Lightbox: %2$s', 'advanced-gallery-repeater-fields-for-acf'),
                    '<strong>' . esc_html($layout_labels[$display_layout] ?? $display_layout) . '</strong>',
                    ($field['enable_lightbox'] ?? 0) ? esc_html__('Enabled', 'advanced-gallery-repeater-fields-for-acf') : esc_html__('Disabled', 'advanced-gallery-repeater-fields-for-acf')
                );
                ?>
            </p>
            
        </div>
        <?php
    }
    
    /**
     * Render a single attachment item
     */
    private function render_attachment($attachment_id, $field) {
        $attachment = get_post($attachment_id);
        
        if (!$attachment) {
            return;
        }
        
        $preview_size = $field['preview_size'] ?? 'medium';
        $thumb = wp_get_attachment_image_src($attachment_id, $preview_size);
        $thumb_url = $thumb ? $thumb[0] : wp_get_attachment_image_src($attachment_id, 'thumbnail')[0];
        
        ?>
        <div class="agrfuxd-gallery-attachment" data-id="<?php echo esc_attr($attachment_id); ?>">
            <input type="hidden" name="<?php echo esc_attr($field['name']); ?>[]" value="<?php echo esc_attr($attachment_id); ?>" />

            <div class="agrfuxd-gallery-attachment-preview">
                <img src="<?php echo esc_url($thumb_url); ?>" alt="" />
            </div>

            <div class="agrfuxd-gallery-attachment-actions">
                <a href="#" class="agrfuxd-edit-attachment acf-icon -pencil small" title="<?php esc_attr_e('Edit', 'advanced-gallery-repeater-fields-for-acf'); ?>"></a>
                <a href="#" class="agrfuxd-remove-attachment acf-icon -minus small" title="<?php esc_attr_e('Remove', 'advanced-gallery-repeater-fields-for-acf'); ?>"></a>
            </div>

            <div class="agrfuxd-gallery-attachment-title"><?php echo esc_html($attachment->post_title); ?></div>
        </div>
        <?php
    }

    /**
     * AJAX handler to get attachment HTML
     */
    public function ajax_get_attachment() {
        check_ajax_referer('agrfuxd_gallery_nonce', 'nonce');

        $attachment_id = intval(wp_unslash($_POST['attachment_id'] ?? 0));
        $field_name = sanitize_text_field(wp_unslash($_POST['field_name'] ?? ''));
        $preview_size = sanitize_text_field(wp_unslash($_POST['preview_size'] ?? 'medium'));
        
        if (!$attachment_id) {
            wp_send_json_error();
        }
        
        $field = array(
            'name' => $field_name,
            'preview_size' => $preview_size,
        );
        
        ob_start();
        $this->render_attachment($attachment_id, $field);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Format value for frontend
     */
    public function format_value($value, $post_id, $field) {
        // Bail early if no value
        if (empty($value)) {
            return $value;
        }

        // Ensure value is an array
        if (!is_array($value)) {
            $value = array($value);
        }

        // Format based on return_format
        $return_format = $field['return_format'] ?? 'array';
        
        // ID format
        if ($return_format === 'id') {
            return array_map('intval', $value);
        }
        
        // URL format
        if ($return_format === 'url') {
            $urls = array();
            foreach ($value as $id) {
                $url = wp_get_attachment_url($id);
                if ($url) {
                    $urls[] = $url;
                }
            }
            return $urls;
        }
        
        // Array format (default)
        $images = array();
        foreach ($value as $id) {
            $image = acf_get_attachment($id);
            if ($image) {
                $images[] = $image;
            }
        }
        
        return $images;
    }

    /**
     * Validate value
     */
    public function validate_value($valid, $value, $field, $input) {
        if (!$valid) {
            return $valid;
        }
        
        // Ensure value is array
        $value = is_array($value) ? $value : array();
        $value = array_filter($value);
        $count = count($value);
        
        // Min validation
        $min = $field['min'] ?? 0;
        if ($min && $count < $min) {
            /* translators: %d: minimum number of images required */
            return sprintf(__('Selection requires at least %d images', 'advanced-gallery-repeater-fields-for-acf'), $min);
        }

        // Max validation
        $max = $field['max'] ?? 0;
        if ($max && $count > $max) {
            /* translators: %d: maximum number of images allowed */
            return sprintf(__('Selection allows a maximum of %d images', 'advanced-gallery-repeater-fields-for-acf'), $max);
        }
        
        return $valid;
    }

    /**
     * Update value before saving
     */
    public function update_value($value, $post_id, $field) {
        // Ensure value is array of IDs
        if (!is_array($value)) {
            $value = array();
        }
        
        // Filter and sanitize
        $value = array_filter(array_map('intval', $value));
        
        return $value;
    }
    
    /**
     * Load value from database
     */
    public function load_value($value, $post_id, $field) {
        // Ensure array
        if (is_string($value) && !empty($value)) {
            $value = maybe_unserialize($value);
        }

        if (!is_array($value)) {
            $value = array();
        }

        return array_filter(array_map('intval', $value));
    }
}

// Note: Gallery render functions have been moved to includes/helper-functions.php
// to ensure they are always available for both ACF and Elementor rendering.
