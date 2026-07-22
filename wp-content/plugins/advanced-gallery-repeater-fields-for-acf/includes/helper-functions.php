<?php
/**
 * Helper Functions
 *
 * Standalone helper functions and shortcodes for the plugin
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced get_sub_field helper function
 *
 * A wrapper around ACF's get_sub_field() with additional formatting options.
 * Use within have_rows() loops for Easy Repeater or Enhanced Repeater fields.
 *
 * @param string $selector Field name or field key
 * @param bool   $format Whether to format the value based on field type (default: true)
 * @return mixed The sub-field value or formatted value
 *
 * @example
 * if (have_rows('team_members')) :
 *     while (have_rows('team_members')) : the_row();
 *         $name = agrfuxd_get_sub_field('name');
 *         $photo = agrfuxd_get_sub_field('photo');
 *         $email = agrfuxd_get_sub_field('email');
 *     endwhile;
 * endif;
 */
function agrfuxd_get_sub_field($selector, $format = true) {
    // Get the raw value from ACF
    $value = get_sub_field($selector);

    // Return null if no value
    if ($value === null || $value === '' || $value === false) {
        return null;
    }

    // If formatting is disabled, return raw value
    if (!$format) {
        return $value;
    }

    // Auto-format based on value type
    if (is_array($value)) {
        // For image/file arrays, return the URL
        if (isset($value['url'])) {
            return $value['url'];
        }
        // For arrays with ID, return as comma-separated string
        if (isset($value['ID'])) {
            return $value['ID'];
        }
    }

    // Return formatted value
    return $value;
}

/**
 * Gallery Shortcode
 *
 * Usage: [agrfuxd_gallery field="field_name" post_id="123"]
 *
 * @param array $atts Shortcode attributes
 * @return string Gallery HTML output
 */
function agrfuxd_gallery_shortcode($atts) {
    $atts = shortcode_atts(array(
        'field' => '',
        'post_id' => null,
    ), $atts);

    if (empty($atts['field'])) {
        return '';
    }

    // Get post ID with multiple fallbacks for Elementor compatibility
    $post_id = null;
    if (!empty($atts['post_id'])) {
        $post_id = intval($atts['post_id']);
    } elseif (get_the_ID()) {
        $post_id = get_the_ID();
    } elseif (isset($GLOBALS['post'])) {
        $post_id = $GLOBALS['post']->ID;
    } elseif (is_singular()) {
        global $wp_query;
        $post_id = $wp_query->get_queried_object_id();
    }

    if (!$post_id) {
        return '<!-- ACF Gallery: No post ID found -->';
    }

    return agrfuxd_render_enhanced_gallery($atts['field'], $post_id);
}
add_shortcode('agrfuxd_gallery', 'agrfuxd_gallery_shortcode');

/**
 * Helper function to render Enhanced Gallery on frontend
 */
function agrfuxd_render_enhanced_gallery($field_name, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $field = get_field_object($field_name, $post_id);

    if (!$field) {
        return '<!-- ACF Gallery Error: Field "' . esc_html($field_name) . '" not found -->';
    }

    if ($field['type'] !== 'enhanced_gallery') {
        return '<!-- ACF Gallery Error: Field "' . esc_html($field_name) . '" is not an Enhanced Gallery field -->';
    }

    $images = $field['value'];

    if (empty($images) || !is_array($images)) {
        return '<!-- ACF Gallery: No images in gallery -->';
    }

    $layout = $field['display_layout'] ?? 'grid';
    $lightbox = $field['enable_lightbox'] ?? true;

    $unique_id = 'agrfuxd-gallery-' . uniqid();

    ob_start();

    switch ($layout) {
        case 'carousel':
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in render function
            echo agrfuxd_render_carousel_gallery($images, $field, $unique_id);
            break;
        case 'masonry':
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in render function
            echo agrfuxd_render_masonry_gallery($images, $field, $unique_id);
            break;
        case 'justified':
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in render function
            echo agrfuxd_render_justified_gallery($images, $field, $unique_id);
            break;
        default:
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in render function
            echo agrfuxd_render_grid_gallery($images, $field, $unique_id);
    }

    if ($lightbox) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in render function
        echo agrfuxd_render_lightbox_modal($images, $unique_id);
    }

    return ob_get_clean();
}

/**
 * Render Grid Gallery
 */
function agrfuxd_render_grid_gallery($images, $field, $unique_id) {
    $columns = $field['columns'] ?? 3;
    $gap = $field['gap'] ?? 15;
    $image_size = $field['image_size'] ?? 'medium';
    $lightbox = $field['enable_lightbox'] ?? true;
    $caption = $field['show_caption'] ?? false;
    $lazy = $field['enable_lazy_load'] ?? true;

    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>"
         class="agrfuxd-gallery agrfuxd-gallery-grid"
         style="--agrfuxd-columns: <?php echo esc_attr($columns); ?>; --agrfuxd-gap: <?php echo esc_attr($gap); ?>px;">

        <?php foreach ($images as $index => $image) :
            $img_src = $image['sizes'][$image_size] ?? $image['url'];
            $full_src = $image['url'];
        ?>
            <div class="agrfuxd-gallery-item" data-index="<?php echo esc_attr($index); ?>">
                <figure class="agrfuxd-figure">
                    <?php if ($lightbox) : ?>
                        <a href="<?php echo esc_url($full_src); ?>"
                           class="agrfuxd-lightbox-trigger"
                           data-gallery="<?php echo esc_attr($unique_id); ?>"
                           data-index="<?php echo esc_attr($index); ?>"
                           data-elementor-open-lightbox="no">
                    <?php endif; ?>

                    <img src="<?php echo esc_url($img_src); ?>"
                         alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                         <?php if ($lazy) : ?>loading="lazy"<?php endif; ?>
                         class="agrfuxd-image">

                    <?php if ($lightbox) : ?>
                        <span class="agrfuxd-zoom-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/><path fill="currentColor" d="M12 10h-2v2H9v-2H7V9h2V7h1v2h2v1z"/></svg>
                        </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($caption && !empty($image['caption'])) : ?>
                        <figcaption class="agrfuxd-caption"><?php echo wp_kses_post($image['caption']); ?></figcaption>
                    <?php endif; ?>
                </figure>
            </div>
        <?php endforeach; ?>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Masonry Gallery
 */
function agrfuxd_render_masonry_gallery($images, $field, $unique_id) {
    $columns = $field['columns'] ?? 3;
    $gap = $field['gap'] ?? 15;
    $image_size = $field['image_size'] ?? 'medium';
    $lightbox = $field['enable_lightbox'] ?? true;
    $caption = $field['show_caption'] ?? false;
    $lazy = $field['enable_lazy_load'] ?? true;

    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>"
         class="agrfuxd-gallery agrfuxd-gallery-masonry"
         data-columns="<?php echo esc_attr($columns); ?>"
         style="--agrfuxd-columns: <?php echo esc_attr($columns); ?>; --agrfuxd-gap: <?php echo esc_attr($gap); ?>px;">

        <div class="agrfuxd-gallery-sizer"></div>

        <?php foreach ($images as $index => $image) :
            $img_src = $image['sizes'][$image_size] ?? $image['url'];
            $full_src = $image['url'];
        ?>
            <div class="agrfuxd-gallery-item agrfuxd-masonry-item" data-index="<?php echo esc_attr($index); ?>">
                <figure class="agrfuxd-figure">
                    <?php if ($lightbox) : ?>
                        <a href="<?php echo esc_url($full_src); ?>"
                           class="agrfuxd-lightbox-trigger"
                           data-gallery="<?php echo esc_attr($unique_id); ?>"
                           data-index="<?php echo esc_attr($index); ?>"
                           data-elementor-open-lightbox="no">
                    <?php endif; ?>

                    <img src="<?php echo esc_url($img_src); ?>"
                         alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                         <?php if ($lazy) : ?>loading="lazy"<?php endif; ?>
                         class="agrfuxd-image">

                    <?php if ($lightbox) : ?>
                        <span class="agrfuxd-zoom-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/><path fill="currentColor" d="M12 10h-2v2H9v-2H7V9h2V7h1v2h2v1z"/></svg>
                        </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($caption && !empty($image['caption'])) : ?>
                        <figcaption class="agrfuxd-caption"><?php echo wp_kses_post($image['caption']); ?></figcaption>
                    <?php endif; ?>
                </figure>
            </div>
        <?php endforeach; ?>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Justified Gallery
 */
function agrfuxd_render_justified_gallery($images, $field, $unique_id) {
    $gap = $field['gap'] ?? 5;
    $image_size = $field['image_size'] ?? 'medium';
    $lightbox = $field['enable_lightbox'] ?? true;
    $caption = $field['show_caption'] ?? false;
    $lazy = $field['enable_lazy_load'] ?? true;

    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>"
         class="agrfuxd-gallery agrfuxd-gallery-justified"
         style="--agrfuxd-gap: <?php echo esc_attr($gap); ?>px;">

        <?php foreach ($images as $index => $image) :
            $img_src = $image['sizes'][$image_size] ?? $image['url'];
            $full_src = $image['url'];
            $width = $image['width'] ?? 1;
            $height = $image['height'] ?? 1;
            $aspect = ($width && $height) ? $width / $height : 1;
        ?>
            <div class="agrfuxd-gallery-item agrfuxd-justified-item"
                 data-index="<?php echo esc_attr($index); ?>"
                 style="flex-grow: <?php echo esc_attr($aspect); ?>;">
                <figure class="agrfuxd-figure">
                    <?php if ($lightbox) : ?>
                        <a href="<?php echo esc_url($full_src); ?>"
                           class="agrfuxd-lightbox-trigger"
                           data-gallery="<?php echo esc_attr($unique_id); ?>"
                           data-index="<?php echo esc_attr($index); ?>"
                           data-elementor-open-lightbox="no">
                    <?php endif; ?>

                    <img src="<?php echo esc_url($img_src); ?>"
                         alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                         <?php if ($lazy) : ?>loading="lazy"<?php endif; ?>
                         class="agrfuxd-image">

                    <?php if ($lightbox) : ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($caption && !empty($image['caption'])) : ?>
                        <figcaption class="agrfuxd-caption"><?php echo wp_kses_post($image['caption']); ?></figcaption>
                    <?php endif; ?>
                </figure>
            </div>
        <?php endforeach; ?>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Carousel Gallery
 */
function agrfuxd_render_carousel_gallery($images, $field, $unique_id) {
    $image_size = $field['image_size'] ?? 'medium_large';
    $caption = $field['show_caption'] ?? false;
    $autoplay = $field['carousel_autoplay'] ?? true;
    $speed = $field['carousel_speed'] ?? 3000;
    $slides_to_show = $field['carousel_slides_to_show'] ?? 3;
    $carousel_height = $field['carousel_height'] ?? 400;
    $dots = $field['carousel_dots'] ?? true;
    $arrows = $field['carousel_arrows'] ?? true;
    $lightbox = $field['enable_lightbox'] ?? true;

    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>"
         class="agrfuxd-carousel"
         data-autoplay="<?php echo esc_attr($autoplay ? 'true' : 'false'); ?>"
         data-speed="<?php echo esc_attr($speed); ?>"
         data-slides-to-show="<?php echo esc_attr($slides_to_show); ?>"
         data-carousel-height="<?php echo esc_attr($carousel_height); ?>">

        <div class="agrfuxd-carousel-wrapper">
            <div class="agrfuxd-carousel-track">
                <?php foreach ($images as $index => $image) :
                    $img_src = $image['sizes'][$image_size] ?? $image['url'];
                    $full_src = $image['url'];
                ?>
                    <div class="agrfuxd-carousel-slide">
                        <figure class="agrfuxd-carousel-figure">
                            <?php if ($lightbox) : ?>
                                <a href="<?php echo esc_url($full_src); ?>"
                                   class="agrfuxd-lightbox-trigger"
                                   data-gallery="<?php echo esc_attr($unique_id); ?>"
                                   data-index="<?php echo esc_attr($index); ?>"
                                   data-elementor-open-lightbox="no">
                            <?php endif; ?>

                            <img src="<?php echo esc_url($img_src); ?>"
                                 alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                                 loading="lazy">

                            <?php if ($lightbox) : ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($caption && !empty($image['caption'])) : ?>
                                <figcaption class="agrfuxd-carousel-caption"><?php echo wp_kses_post($image['caption']); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($arrows) : ?>
            <button class="agrfuxd-carousel-arrow agrfuxd-carousel-prev" aria-label="<?php esc_attr_e('Previous', 'advanced-gallery-repeater-fields-for-acf'); ?>">
                <svg viewBox="0 0 24 24" width="32" height="32"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
            </button>
            <button class="agrfuxd-carousel-arrow agrfuxd-carousel-next" aria-label="<?php esc_attr_e('Next', 'advanced-gallery-repeater-fields-for-acf'); ?>">
                <svg viewBox="0 0 24 24" width="32" height="32"><path fill="currentColor" d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z"/></svg>
            </button>
        <?php endif; ?>

        <?php if ($dots) : ?>
            <div class="agrfuxd-carousel-dots">
                <?php for ($i = 0; $i < ceil(count($images) / $slides_to_show); $i++) : ?>
                    <button class="agrfuxd-carousel-dot <?php echo esc_attr($i === 0 ? 'active' : ''); ?>"
                            data-index="<?php echo esc_attr($i); ?>"></button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Lightbox Modal
 */
function agrfuxd_render_lightbox_modal($images, $unique_id) {
    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>-lightbox" class="agrfuxd-lightbox" data-gallery="<?php echo esc_attr($unique_id); ?>" aria-hidden="true">
        <div class="agrfuxd-lightbox-overlay"></div>

        <div class="agrfuxd-lightbox-container">
            <button class="agrfuxd-lightbox-close" aria-label="<?php esc_attr_e('Close', 'advanced-gallery-repeater-fields-for-acf'); ?>">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>

            <button class="agrfuxd-lightbox-nav agrfuxd-lightbox-prev" aria-label="<?php esc_attr_e('Previous', 'advanced-gallery-repeater-fields-for-acf'); ?>">
                <svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
            </button>

            <div class="agrfuxd-lightbox-content">
                <div class="agrfuxd-lightbox-image-wrapper">
                    <img class="agrfuxd-lightbox-image" src="" alt="">
                    <div class="agrfuxd-lightbox-loader"><div class="agrfuxd-spinner"></div></div>
                </div>
                <div class="agrfuxd-lightbox-info">
                    <h4 class="agrfuxd-lightbox-title"></h4>
                    <p class="agrfuxd-lightbox-caption"></p>
                    <span class="agrfuxd-lightbox-counter"></span>
                </div>
            </div>

            <button class="agrfuxd-lightbox-nav agrfuxd-lightbox-next" aria-label="<?php esc_attr_e('Next', 'advanced-gallery-repeater-fields-for-acf'); ?>">
                <svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z"/></svg>
            </button>
        </div>

        <div class="agrfuxd-lightbox-thumbnails">
            <?php foreach ($images as $index => $image) : ?>
                <button class="agrfuxd-lightbox-thumb"
                        data-index="<?php echo esc_attr($index); ?>"
                        data-src="<?php echo esc_url($image['url']); ?>"
                        data-title="<?php echo esc_attr($image['title'] ?? ''); ?>"
                        data-caption="<?php echo esc_attr($image['caption'] ?? ''); ?>">
                    <img src="<?php echo esc_url($image['sizes']['thumbnail'] ?? $image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>">
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
