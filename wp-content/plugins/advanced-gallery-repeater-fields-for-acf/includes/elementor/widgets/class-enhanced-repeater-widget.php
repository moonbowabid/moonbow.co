<?php
/**
 * Enhanced Repeater Elementor Widget
 *
 * Displays ACF Enhanced Repeater field with multiple layout options
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AGRFUXD_Enhanced_Repeater_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'agrfuxd_enhanced_repeater';
    }

    public function get_title() {
        return __('ACF Enhanced Repeater', 'advanced-gallery-repeater-fields-for-acf');
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['general', 'agrfuxd'];
    }

    public function get_keywords() {
        return ['repeater', 'acf', 'grid', 'list', 'accordion', 'tabs', 'timeline', 'cards'];
    }

    public function get_style_depends() {
        return ['agrfuxd-frontend', 'agrfuxd-elementor-widgets'];
    }

    public function get_script_depends() {
        return ['agrfuxd-frontend', 'agrfuxd-elementor-widgets'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Content', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater_fields = $this->get_acf_repeater_fields();

        $this->add_control(
            'acf_field',
            [
                'label' => __('ACF Repeater Field', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $repeater_fields,
                'default' => '',
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
                    'list' => __('List', 'advanced-gallery-repeater-fields-for-acf'),
                    'accordion' => __('Accordion', 'advanced-gallery-repeater-fields-for-acf'),
                    'tabs' => __('Tabs', 'advanced-gallery-repeater-fields-for-acf'),
                    'table' => __('Table', 'advanced-gallery-repeater-fields-for-acf'),
                ],
            ]
        );

        $this->add_control(
            'preview_post_id',
            [
                'label' => __('Preview Post ID', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'description' => __('Enter a Post/Product ID to preview data.', 'advanced-gallery-repeater-fields-for-acf'),
            ]
        );

        $this->end_controls_section();

        // Template Section
        $this->start_controls_section(
            'section_template',
            [
                'label' => __('Template', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title_field',
            [
                'label' => __('Title Field Name', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'title',
                'description' => __('Sub-field name for title', 'advanced-gallery-repeater-fields-for-acf'),
            ]
        );

        $this->add_control(
            'content_field',
            [
                'label' => __('Content Field Name', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'description',
                'description' => __('Sub-field name for content/description', 'advanced-gallery-repeater-fields-for-acf'),
            ]
        );

        $this->add_control(
            'image_field',
            [
                'label' => __('Image Field Name', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'image',
                'description' => __('Sub-field name for image (optional)', 'advanced-gallery-repeater-fields-for-acf'),
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
                    '{{WRAPPER}} .agrfuxd-repeater-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
                ],
                'condition' => [
                    'layout' => 'grid',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => __('Gap', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 100],
                ],
                'default' => ['size' => 20, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-grid, {{WRAPPER}} .agrfuxd-repeater-list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_layout',
            [
                'label' => __('Item Layout', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'stacked',
                'options' => [
                    'stacked' => __('Stacked (Vertical)', 'advanced-gallery-repeater-fields-for-acf'),
                    'horizontal' => __('Side by Side (Horizontal)', 'advanced-gallery-repeater-fields-for-acf'),
                    'horizontal-reverse' => __('Side by Side (Image Right)', 'advanced-gallery-repeater-fields-for-acf'),
                ],
                'condition' => [
                    'layout' => ['grid', 'list'],
                ],
            ]
        );

        $this->add_responsive_control(
            'image_width',
            [
                'label' => __('Image Width', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 50, 'max' => 500],
                    '%' => ['min' => 10, 'max' => 50],
                ],
                'default' => ['size' => 30, 'unit' => '%'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-horizontal .agrfuxd-item-image, {{WRAPPER}} .agrfuxd-item-horizontal-reverse .agrfuxd-item-image' => 'width: {{SIZE}}{{UNIT}}; flex-shrink: 0;',
                ],
                'condition' => [
                    'item_layout' => ['horizontal', 'horizontal-reverse'],
                ],
            ]
        );

        $this->add_responsive_control(
            'vertical_align',
            [
                'label' => __('Vertical Alignment', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => __('Top', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => __('Middle', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => __('Bottom', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-horizontal, {{WRAPPER}} .agrfuxd-item-horizontal-reverse' => 'align-items: {{VALUE}};',
                ],
                'condition' => [
                    'item_layout' => ['horizontal', 'horizontal-reverse'],
                ],
            ]
        );

        $this->add_control(
            'image_position',
            [
                'label' => __('Image Position', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'top',
                'options' => [
                    'top' => __('Top', 'advanced-gallery-repeater-fields-for-acf'),
                    'bottom' => __('Bottom', 'advanced-gallery-repeater-fields-for-acf'),
                ],
                'condition' => [
                    'item_layout' => 'stacked',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Item Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => __('Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_padding',
            [
                'label' => __('Padding', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'default' => [
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 20,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_border_radius',
            [
                'label' => __('Border Radius', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => ['px' => ['min' => 0, 'max' => 30]],
                'default' => ['size' => 8, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-item' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'item_shadow',
                'selector' => '{{WRAPPER}} .agrfuxd-repeater-item',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .agrfuxd-repeater-item',
            ]
        );

        $this->end_controls_section();

        // Title Style Section
        $this->start_controls_section(
            'section_title_style',
            [
                'label' => __('Title Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-title, {{WRAPPER}} .agrfuxd-repeater-item h3, {{WRAPPER}} .agrfuxd-accordion-title, {{WRAPPER}} .agrfuxd-tab-title, {{WRAPPER}} .agrfuxd-timeline-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'advanced-gallery-repeater-fields-for-acf'),
                'selector' => '{{WRAPPER}} .agrfuxd-item-title, {{WRAPPER}} .agrfuxd-repeater-item h3, {{WRAPPER}} .agrfuxd-accordion-title, {{WRAPPER}} .agrfuxd-tab-title, {{WRAPPER}} .agrfuxd-timeline-title',
            ]
        );

        $this->add_responsive_control(
            'title_spacing',
            [
                'label' => __('Title Spacing', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'default' => ['size' => 10, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-title, {{WRAPPER}} .agrfuxd-repeater-item h3' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'title_align',
            [
                'label' => __('Title Alignment', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-title, {{WRAPPER}} .agrfuxd-repeater-item h3' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Content Style Section
        $this->start_controls_section(
            'section_content_style',
            [
                'label' => __('Content Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label' => __('Content Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-content, {{WRAPPER}} .agrfuxd-accordion-content, {{WRAPPER}} .agrfuxd-tab-panel, {{WRAPPER}} .agrfuxd-timeline-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label' => __('Content Typography', 'advanced-gallery-repeater-fields-for-acf'),
                'selector' => '{{WRAPPER}} .agrfuxd-item-content, {{WRAPPER}} .agrfuxd-accordion-content, {{WRAPPER}} .agrfuxd-tab-panel, {{WRAPPER}} .agrfuxd-timeline-content, {{WRAPPER}} .agrfuxd-repeater-item p',
            ]
        );

        $this->add_responsive_control(
            'content_align',
            [
                'label' => __('Content Alignment', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justify', 'advanced-gallery-repeater-fields-for-acf'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-content, {{WRAPPER}} .agrfuxd-accordion-content, {{WRAPPER}} .agrfuxd-tab-panel, {{WRAPPER}} .agrfuxd-repeater-item p' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Image Style Section
        $this->start_controls_section(
            'section_image_style',
            [
                'label' => __('Image Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => ['grid', 'list'],
                ],
            ]
        );

        $this->add_responsive_control(
            'image_border_radius',
            [
                'label' => __('Border Radius', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 4,
                    'right' => 4,
                    'bottom' => 4,
                    'left' => 4,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'selector' => '{{WRAPPER}} .agrfuxd-item-image img',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'image_shadow',
                'selector' => '{{WRAPPER}} .agrfuxd-item-image img',
            ]
        );

        $this->add_control(
            'image_object_fit',
            [
                'label' => __('Object Fit', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => __('Cover', 'advanced-gallery-repeater-fields-for-acf'),
                    'contain' => __('Contain', 'advanced-gallery-repeater-fields-for-acf'),
                    'fill' => __('Fill', 'advanced-gallery-repeater-fields-for-acf'),
                    'none' => __('None', 'advanced-gallery-repeater-fields-for-acf'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-image img' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_height',
            [
                'label' => __('Image Height', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => ['min' => 50, 'max' => 600],
                    'vh' => ['min' => 10, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-image img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_spacing',
            [
                'label' => __('Image Spacing', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'default' => ['size' => 15, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-item-stacked .agrfuxd-item-image' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .agrfuxd-item-horizontal, {{WRAPPER}} .agrfuxd-item-horizontal-reverse' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Table Style Section
        $this->start_controls_section(
            'section_table_style',
            [
                'label' => __('Table Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => 'table',
                ],
            ]
        );

        $this->add_control(
            'table_header_bg',
            [
                'label' => __('Header Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-table th' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_header_color',
            [
                'label' => __('Header Text Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-table th' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'table_header_typography',
                'label' => __('Header Typography', 'advanced-gallery-repeater-fields-for-acf'),
                'selector' => '{{WRAPPER}} .agrfuxd-repeater-table th',
            ]
        );

        $this->add_control(
            'table_cell_color',
            [
                'label' => __('Cell Text Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-table td' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'table_cell_typography',
                'label' => __('Cell Typography', 'advanced-gallery-repeater-fields-for-acf'),
                'selector' => '{{WRAPPER}} .agrfuxd-repeater-table td',
            ]
        );

        $this->add_control(
            'table_border_color',
            [
                'label' => __('Border Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-table th, {{WRAPPER}} .agrfuxd-repeater-table td' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_row_hover_bg',
            [
                'label' => __('Row Hover Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-table tbody tr:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'table_cell_padding',
            [
                'label' => __('Cell Padding', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'default' => [
                    'top' => 12,
                    'right' => 15,
                    'bottom' => 12,
                    'left' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-repeater-table th, {{WRAPPER}} .agrfuxd-repeater-table td' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Accordion Style Section
        $this->start_controls_section(
            'section_accordion_style',
            [
                'label' => __('Accordion Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => 'accordion',
                ],
            ]
        );

        $this->add_control(
            'accordion_header_bg',
            [
                'label' => __('Header Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-accordion-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'accordion_header_active_bg',
            [
                'label' => __('Active Header Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e9ecef',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-accordion-header.active, {{WRAPPER}} .agrfuxd-accordion-header:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'accordion_header_color',
            [
                'label' => __('Header Text Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-accordion-header' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'accordion_header_typography',
                'label' => __('Header Typography', 'advanced-gallery-repeater-fields-for-acf'),
                'selector' => '{{WRAPPER}} .agrfuxd-accordion-header',
            ]
        );

        $this->add_control(
            'accordion_icon_color',
            [
                'label' => __('Icon Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-accordion-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'accordion_border_color',
            [
                'label' => __('Border Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-accordion-item' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .agrfuxd-accordion-content' => 'border-top-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Tabs Style Section
        $this->start_controls_section(
            'section_tabs_style',
            [
                'label' => __('Tabs Style', 'advanced-gallery-repeater-fields-for-acf'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout' => 'tabs',
                ],
            ]
        );

        $this->add_control(
            'tabs_nav_bg',
            [
                'label' => __('Tab Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-tab-title' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_nav_active_bg',
            [
                'label' => __('Active Tab Background', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#3b82f6',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-tab-title.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_nav_color',
            [
                'label' => __('Tab Text Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-tab-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_nav_active_color',
            [
                'label' => __('Active Tab Text Color', 'advanced-gallery-repeater-fields-for-acf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .agrfuxd-tab-title.active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'tabs_nav_typography',
                'label' => __('Tab Typography', 'advanced-gallery-repeater-fields-for-acf'),
                'selector' => '{{WRAPPER}} .agrfuxd-tab-title',
            ]
        );

        $this->end_controls_section();
    }

    private function get_acf_repeater_fields() {
        $fields = ['' => __('-- Select Field --', 'advanced-gallery-repeater-fields-for-acf')];

        if (!function_exists('acf_get_field_groups')) {
            return $fields;
        }

        $field_groups = acf_get_field_groups();
        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['key']);
            if (!$group_fields) continue;

            foreach ($group_fields as $field) {
                if (in_array($field['type'], ['repeater', 'enhanced_repeater'])) {
                    $fields[$field['name']] = $group['title'] . ' - ' . $field['label'];
                }
            }
        }

        return $fields;
    }

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

        return get_the_ID();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        // Check if field is selected
        if (empty($settings['acf_field'])) {
            if ($is_editor) {
                echo '<div class="agrfuxd-elementor-notice"><p>' . esc_html__('Please select an ACF Repeater field.', 'advanced-gallery-repeater-fields-for-acf') . '</p></div>';
            }
            return;
        }

        // Get post ID
        $post_id = $this->get_current_post_id();
        if (!empty($settings['preview_post_id'])) {
            $post_id = intval($settings['preview_post_id']);
        }

        // Get field data
        $rows = get_field($settings['acf_field'], $post_id);

        // Debug in editor
        if (empty($rows) || !is_array($rows)) {
            if ($is_editor) {
                echo '<div class="agrfuxd-elementor-notice">';
                echo '<p>' . esc_html__('No data found.', 'advanced-gallery-repeater-fields-for-acf') . '</p>';
                echo '<p><small>Post ID: ' . esc_html($post_id) . ' | Field: ' . esc_html($settings['acf_field']) . '</small></p>';
                echo '</div>';
            }
            return;
        }

        $layout = $settings['layout'];
        $title_field = $settings['title_field'] ?? '';
        $content_field = $settings['content_field'] ?? '';
        $image_field = $settings['image_field'] ?? '';

        echo '<div class="agrfuxd-elementor-repeater agrfuxd-repeater-' . esc_attr($layout) . '">';

        switch ($layout) {
            case 'accordion':
                $this->render_accordion($rows, $title_field, $content_field);
                break;
            case 'tabs':
                $this->render_tabs($rows, $title_field, $content_field);
                break;
            case 'table':
                $this->render_table($rows);
                break;
            case 'list':
            case 'grid':
            default:
                $this->render_grid($rows, $layout, $title_field, $content_field, $image_field, $settings);
        }

        echo '</div>';
    }

    /**
     * Get image URL from various formats (ID, array, or URL string)
     */
    private function get_image_data($img) {
        $result = ['url' => '', 'alt' => ''];

        if (empty($img)) {
            return $result;
        }

        // If it's an array with URL (ACF array format)
        if (is_array($img)) {
            $result['url'] = $img['url'] ?? '';
            $result['alt'] = $img['alt'] ?? '';
            return $result;
        }

        // If it's a numeric ID
        if (is_numeric($img)) {
            $img_id = intval($img);
            $img_url = wp_get_attachment_image_url($img_id, 'medium');
            if ($img_url) {
                $result['url'] = $img_url;
                $result['alt'] = get_post_meta($img_id, '_wp_attachment_image_alt', true);
            }
            return $result;
        }

        // If it's a URL string
        if (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
            $result['url'] = $img;
            return $result;
        }

        return $result;
    }

    private function render_grid($rows, $layout, $title_field, $content_field, $image_field, $settings) {
        $class = ($layout === 'list') ? 'agrfuxd-repeater-list' : 'agrfuxd-repeater-grid';
        $item_layout = $settings['item_layout'] ?? 'stacked';
        $image_position = $settings['image_position'] ?? 'top';

        
        foreach ($rows as $row) {
            $item_class = 'agrfuxd-repeater-item agrfuxd-item-' . esc_attr($item_layout);

            echo '<div class="' . esc_attr($item_class) . '">';

            // For stacked layout with bottom image position, render content first
            if ($item_layout === 'stacked' && $image_position === 'bottom') {
                $this->render_item_content($row, $title_field, $content_field, $image_field);
                $this->render_item_image($row, $image_field);
            } else {
                // Default: image first, then content
                $this->render_item_image($row, $image_field);
                $this->render_item_content($row, $title_field, $content_field, $image_field);
            }

            echo '</div>';
        }
        
    }

    private function render_item_image($row, $image_field) {
        if (empty($image_field) || empty($row[$image_field])) {
            return;
        }

        $img_data = $this->get_image_data($row[$image_field]);

        if (!empty($img_data['url'])) {
            echo '<div class="agrfuxd-item-image">';
            echo '<img src="' . esc_url($img_data['url']) . '" alt="' . esc_attr($img_data['alt']) . '">';
            echo '</div>';
        }
    }

    private function render_item_content($row, $title_field, $content_field, $image_field) {
        echo '<div class="agrfuxd-item-text">';

        // Title
        if (!empty($title_field) && !empty($row[$title_field])) {
            echo '<h3 class="agrfuxd-item-title">' . esc_html($row[$title_field]) . '</h3>';
        }

        // Content
        if (!empty($content_field) && !empty($row[$content_field])) {
            echo '<div class="agrfuxd-item-content">' . wp_kses_post($row[$content_field]) . '</div>';
        }

        // Auto display other fields if no specific fields set
        if (empty($title_field) && empty($content_field)) {
            foreach ($row as $key => $value) {
                // Skip the image field
                if ($key === $image_field) {
                    continue;
                }

                if (is_array($value)) {
                    // Check if it's an image array
                    if (isset($value['url'])) {
                        echo '<div class="agrfuxd-item-image"><img src="' . esc_url($value['url']) . '" alt=""></div>';
                    }
                } elseif (is_numeric($value)) {
                    // Check if it might be an attachment ID
                    $img_url = wp_get_attachment_image_url(intval($value), 'medium');
                    if ($img_url) {
                        echo '<div class="agrfuxd-item-image"><img src="' . esc_url($img_url) . '" alt=""></div>';
                    } else {
                        echo '<p>' . wp_kses_post($value) . '</p>';
                    }
                } else {
                    echo '<p>' . wp_kses_post($value) . '</p>';
                }
            }
        }

        echo '</div>';
    }

    private function render_accordion($rows, $title_field, $content_field) {
        echo '<div class="agrfuxd-repeater-accordion">';
        foreach ($rows as $index => $row) {
            $title = !empty($title_field) && !empty($row[$title_field]) ? $row[$title_field] : 'Item ' . ($index + 1);
            $is_open = ($index === 0);

            echo '<div class="agrfuxd-accordion-item' . ($is_open ? ' active' : '') . '">';
            echo '<button class="agrfuxd-accordion-header' . ($is_open ? ' active' : '') . '">';
            echo '<span>' . esc_html($title) . '</span>';
            echo '<span class="agrfuxd-accordion-icon">&#9662;</span>';
            echo '</button>';
            echo '<div class="agrfuxd-accordion-content agrfuxd-repeater-item"' . ($is_open ? '' : ' style="display:none;"') . '>';

            if (!empty($content_field) && !empty($row[$content_field])) {
                echo wp_kses_post($row[$content_field]);
            } else {
                foreach ($row as $key => $value) {
                    if ($key !== $title_field && !is_array($value)) {
                        echo '<p>' . wp_kses_post($value) . '</p>';
                    }
                }
            }

            echo '</div></div>';
        }
        echo '</div>';
    }

    private function render_tabs($rows, $title_field, $content_field) {
        $id = 'tabs-' . $this->get_id();

        echo '<div class="agrfuxd-repeater-tabs">';

        // Tab headers
        echo '<div class="agrfuxd-tabs-nav">';
        foreach ($rows as $index => $row) {
            $title = !empty($title_field) && !empty($row[$title_field]) ? $row[$title_field] : 'Tab ' . ($index + 1);
            $active = ($index === 0) ? ' active' : '';
            echo '<button class="agrfuxd-tab-title' . $active . '" data-tab="' . esc_attr($id . '-' . $index) . '">' . esc_html($title) . '</button>';
        }
        echo '</div>';

        // Tab content
        echo '<div class="agrfuxd-tabs-content">';
        foreach ($rows as $index => $row) {
            $active = ($index === 0) ? ' active' : '';
            $display = ($index === 0) ? '' : ' style="display:none;"';

            echo '<div class="agrfuxd-tab-panel agrfuxd-repeater-item' . $active . '" id="' . esc_attr($id . '-' . $index) . '"' . $display . '>';

            if (!empty($content_field) && !empty($row[$content_field])) {
                echo wp_kses_post($row[$content_field]);
            } else {
                foreach ($row as $key => $value) {
                    if ($key !== $title_field && !is_array($value)) {
                        echo '<p>' . wp_kses_post($value) . '</p>';
                    }
                }
            }

            echo '</div>';
        }
        echo '</div></div>';
    }

    private function render_table($rows) {
        if (empty($rows)) return;

        $first_row = reset($rows);
        if (!is_array($first_row)) return;

        // Find columns that have at least one non-empty value across all rows
        $columns_with_data = array();
        $all_keys = array_keys($first_row);

        foreach ($all_keys as $key) {
            foreach ($rows as $row) {
                if (isset($row[$key])) {
                    $value = $row[$key];
                    // Check if value is not empty
                    if (is_array($value)) {
                        // For arrays (like images), check if url exists
                        if (!empty($value['url']) || !empty($value['id']) || !empty($value)) {
                            $columns_with_data[$key] = true;
                            break;
                        }
                    } elseif (!empty($value) && $value !== '' && $value !== null) {
                        $columns_with_data[$key] = true;
                        break;
                    }
                }
            }
        }

        // If no columns have data, return
        if (empty($columns_with_data)) {
            return;
        }

        // Get only the keys that have data
        $active_columns = array_keys($columns_with_data);

        echo '<div class="agrfuxd-repeater-table-wrapper">';
        echo '<table class="agrfuxd-repeater-table">';

        // Headers - only show columns with data
        echo '<thead><tr>';
        foreach ($active_columns as $key) {
            echo '<th>' . esc_html(ucwords(str_replace(['_', '-'], ' ', $key))) . '</th>';
        }
        echo '</tr></thead>';

        // Rows - only show cells for columns with data
        echo '<tbody>';
        foreach ($rows as $row) {
            echo '<tr class="agrfuxd-repeater-item">';
            foreach ($active_columns as $key) {
                $value = isset($row[$key]) ? $row[$key] : '';
                echo '<td>';
                if (is_array($value) && isset($value['url'])) {
                    echo '<img src="' . esc_url($value['url']) . '" style="max-width:80px;">';
                } elseif (!is_array($value)) {
                    echo wp_kses_post($value);
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    protected function content_template() {
        ?>
        <# if (!settings.acf_field) { #>
            <div class="agrfuxd-elementor-notice">
                <p><?php esc_html_e('Please select an ACF Repeater field.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
            </div>
        <# } else { #>
            <div class="agrfuxd-elementor-notice">
                <p><?php esc_html_e('Repeater will display on frontend.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                <p><small>Layout: {{ settings.layout }} | Field: {{ settings.acf_field }}</small></p>
            </div>
        <# } #>
        <?php
    }
}
