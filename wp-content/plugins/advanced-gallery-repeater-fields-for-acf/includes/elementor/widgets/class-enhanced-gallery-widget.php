<?php
/**
 * Enhanced Gallery Elementor Widget
 *
 * Displays ACF Enhanced Gallery field with multiple layout options
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AGRFUXD_Enhanced_Gallery_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'agrfuxd_enhanced_gallery';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('ACF Enhanced Gallery', 'advanced-gallery-repeater-fields-for-acf');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['general', 'agrfuxd'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['gallery', 'acf', 'images', 'grid', 'masonry', 'carousel', 'lightbox'];
    }

    /**
     * Get style dependencies
     */
    public function get_style_depends() {
        return ['agrfuxd-frontend', 'agrfuxd-lightbox', 'agrfuxd-elementor-widgets'];
    }

    /**
     * Get script dependencies
     */
    public function get_script_depends() {
        return ['agrfuxd-frontend', 'masonry'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Content', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Get ACF gallery fields
        $gallery_fields = $this->get_acf_gallery_fields();

        $this->add_control(
            'acf_field',
            [
                'label' => __('ACF Gallery Field', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $gallery_fields,
                'default' => '',
                'description' => __('Select an ACF Enhanced Gallery or Gallery field', 'advanced-gallery-repeater-fields-for-acf'),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Grid', 'advanced-gallery-repeater-fields-for-acf'),
                    'masonry' => __('Masonry', 'advanced-gallery-repeater-fields-for-acf'),
                    'carousel' => __('Carousel / Slider', 'advanced-gallery-repeater-fields-for-acf'),
                    'justified' => __('Justified', 'advanced-gallery-repeater-fields-for-acf'),
                ],
            ]
        );

        $this->add_control(
            'preview_post_id',
            [
                'label' => __('Preview Post ID', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'description' => __('Enter a Post/Product ID to preview the field data in the editor. Leave empty for automatic detection on frontend.', 'advanced-gallery-repeater-fields-for-acf'),
                'default' => '',
            ]
        );

        $this->end_controls_section();

        // Layout Settings Section
        $this->start_controls_section(
            'section_layout',
            [
                'label' => __('Layout Settings', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Columns', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-gallery-grid, {{WRAPPER}} .agrfuxd-gallery-masonry' => '--agrfuxd-columns: {{VALUE}};',
                ],
                'condition' => [
                    'layout!' => 'carousel',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => __('Gap', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-gallery' => '--agrfuxd-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Image Size', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium_large',
                'options' => $this->get_image_sizes(),
            ]
        );

        $this->end_controls_section();

        // Lightbox Section
        $this->start_controls_section(
            'section_lightbox',
            [
                'label' => __('Lightbox', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_lightbox',
            [
                'label' => __('Enable Lightbox', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_caption',
            [
                'label' => __('Show Captions', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'lazy_load',
            [
                'label' => __('Lazy Loading', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Carousel Section
        $this->start_controls_section(
            'section_carousel',
            [
                'label' => __('Carousel Settings', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'layout' => 'carousel',
                ],
            ]
        );

        $this->add_control(
            'carousel_autoplay',
            [
                'label' => __('Autoplay', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'carousel_speed',
            [
                'label' => __('Autoplay Speed (ms)', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3000,
                'min' => 1000,
                'max' => 10000,
                'step' => 500,
                'condition' => [
                    'carousel_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'slides_to_show',
            [
                'label' => __('Slides to Show', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
            ]
        );

        $this->add_control(
            'carousel_height',
            [
                'label' => __('Carousel Height', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1000,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-carousel-slide img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
                ],
            ]
        );

        $this->add_control(
            'carousel_dots',
            [
                'label' => __('Show Dots', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'carousel_arrows',
            [
                'label' => __('Show Arrows', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section - Images
        $this->start_controls_section(
            'section_style_images',
            [
                'label' => __('Images', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => __('Border Radius', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-gallery-item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'image_box_shadow',
                'selector' => '{{WRAPPER}} .agrfuxd-gallery-item img',
            ]
        );

        $this->add_control(
            'hover_animation',
            [
                'label' => __('Hover Animation', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'zoom',
                'options' => [
                    'none' => __('None', 'advanced-gallery-repeater-fields-for-acf'),
                    'zoom' => __('Zoom', 'advanced-gallery-repeater-fields-for-acf'),
                    'lift' => __('Lift', 'advanced-gallery-repeater-fields-for-acf'),
                    'fade' => __('Fade', 'advanced-gallery-repeater-fields-for-acf'),
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Captions
        $this->start_controls_section(
            'section_style_captions',
            [
                'label' => __('Captions', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_caption' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'caption_color',
            [
                'label' => __('Text Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-caption' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'caption_background',
            [
                'label' => __('Background Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-caption' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'caption_typography',
                'selector' => '{{WRAPPER}} .agrfuxd-caption',
            ]
        );

        $this->add_responsive_control(
            'caption_padding',
            [
                'label' => __('Padding', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-caption' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get ACF gallery fields
     */
    private function get_acf_gallery_fields() {
        $fields = ['' => __('Select Field', 'advanced-gallery-repeater-fields-for-acf')];

        if (!function_exists('acf_get_field_groups')) {
            return $fields;
        }

        $field_groups = acf_get_field_groups();

        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['key']);

            if (!$group_fields) {
                continue;
            }

            foreach ($group_fields as $field) {
                if (in_array($field['type'], ['gallery', 'enhanced_gallery'])) {
                    $fields[$field['name']] = $group['title'] . ' - ' . $field['label'];
                }
            }
        }

        return $fields;
    }

    /**
     * Get available image sizes
     */
    private function get_image_sizes() {
        $sizes = [];
        $registered_sizes = get_intermediate_image_sizes();

        foreach ($registered_sizes as $size) {
            $sizes[$size] = ucwords(str_replace(['_', '-'], ' ', $size));
        }

        $sizes['full'] = __('Full Size', 'advanced-gallery-repeater-fields-for-acf');

        return $sizes;
    }

    /**
     * Get current post ID with Elementor and WooCommerce support
     */
    private function get_current_post_id() {
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        // In editor mode, use Elementor's document context
        if ($is_editor) {
            $document = \Elementor\Plugin::$instance->documents->get_current();
            if ($document) {
                return $document->get_main_id();
            }
        }

        // On frontend, prioritize the actual page being viewed

        // WooCommerce single product page - use queried object
        if (function_exists('is_product') && is_product()) {
            $queried_id = get_queried_object_id();
            if ($queried_id) {
                return $queried_id;
            }
            // Fallback to global product
            global $product;
            if ($product && is_a($product, 'WC_Product')) {
                return $product->get_id();
            }
        }

        // Check queried object (works for most single pages/posts)
        $queried_id = get_queried_object_id();
        if ($queried_id) {
            return $queried_id;
        }

        // Try global post
        global $post;
        if ($post && isset($post->ID)) {
            return $post->ID;
        }

        // Fallback to get_the_ID()
        return get_the_ID();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if (empty($settings['acf_field'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="agrfuxd-elementor-notice">';
                echo '<p>' . esc_html__('Please select an ACF Gallery field from the widget settings.', 'advanced-gallery-repeater-fields-for-acf') . '</p>';
                echo '</div>';
            }
            return;
        }

        // Get the current post ID with proper context handling
        $post_id = $this->get_current_post_id();
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        // In editor mode, use preview post ID if specified
        if ($is_editor && !empty($settings['preview_post_id'])) {
            $post_id = intval($settings['preview_post_id']);
        }

        // Get the field value with explicit post ID
        $images = null;
        if ($post_id) {
            $images = get_field($settings['acf_field'], $post_id);
        }

        if (empty($images)) {
            if ($is_editor) {
                echo '<div class="agrfuxd-elementor-notice">';
                echo '<p>' . esc_html__('No images found in the selected gallery field.', 'advanced-gallery-repeater-fields-for-acf') . '</p>';
                echo '<p><small>' . sprintf(esc_html__('Post ID: %s | Field: %s', 'advanced-gallery-repeater-fields-for-acf'), $post_id ? $post_id : 'Not found', esc_html($settings['acf_field'])) . '</small></p>';
                echo '<p><small>' . esc_html__('Tip: Enter a Post/Product ID in "Preview Post ID" setting to preview data in the editor.', 'advanced-gallery-repeater-fields-for-acf') . '</small></p>';
                echo '</div>';
            }
            return;
        }

        // Ensure images is an array of arrays (not just IDs)
        if (isset($images[0]) && is_numeric($images[0])) {
            $images = array_map(function($id) {
                return acf_get_attachment($id);
            }, $images);
            $images = array_filter($images);
        }

        $unique_id = 'agrfuxd-gallery-' . $this->get_id();
        $layout = $settings['layout'];
        $lightbox = $settings['enable_lightbox'] === 'yes';
        $caption = $settings['show_caption'] === 'yes';
        $lazy = $settings['lazy_load'] === 'yes';
        $image_size = $settings['image_size'];
        $hover_class = 'agrfuxd-hover-' . $settings['hover_animation'];

        // Build field array for render functions
        $field = [
            'columns' => $settings['columns'],
            'gap' => $settings['gap']['size'] ?? 15,
            'image_size' => $image_size,
            'enable_lightbox' => $lightbox,
            'show_caption' => $caption,
            'enable_lazy_load' => $lazy,
            'carousel_autoplay' => $settings['carousel_autoplay'] === 'yes',
            'carousel_speed' => $settings['carousel_speed'] ?? 3000,
            'carousel_slides_to_show' => $settings['slides_to_show'] ?? 3,
            'carousel_height' => $settings['carousel_height']['size'] ?? 400,
            'carousel_dots' => $settings['carousel_dots'] === 'yes',
            'carousel_arrows' => $settings['carousel_arrows'] === 'yes',
        ];

        echo '<div class="agrfuxd-elementor-gallery ' . esc_attr($hover_class) . '">';

        switch ($layout) {
            case 'carousel':
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo agrfuxd_render_carousel_gallery($images, $field, $unique_id);
                break;
            case 'masonry':
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo agrfuxd_render_masonry_gallery($images, $field, $unique_id);
                break;
            case 'justified':
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo agrfuxd_render_justified_gallery($images, $field, $unique_id);
                break;
            default:
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo agrfuxd_render_grid_gallery($images, $field, $unique_id);
        }

        if ($lightbox) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo agrfuxd_render_lightbox_modal($images, $unique_id);
        }

        echo '</div>';
    }

    /**
     * Render widget output in the editor (content template)
     */
    protected function content_template() {
        ?>
        <# if (!settings.acf_field) { #>
            <div class="agrfuxd-elementor-notice">
                <p><?php esc_html_e('Please select an ACF Gallery field from the widget settings.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
            </div>
        <# } else { #>
            <div class="agrfuxd-elementor-notice">
                <p><?php esc_html_e('Gallery preview will appear on the frontend.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
            </div>
        <# } #>
        <?php
    }
}
