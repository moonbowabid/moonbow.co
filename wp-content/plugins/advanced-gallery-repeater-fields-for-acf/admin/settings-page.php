<?php
/**
 * Settings Page Template
 *
 * @package AGRFUXD_Gallery_Repeater_Addon
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap agrfuxd-settings-wrap">
    <!-- Header -->
    <div class="agrfuxd-header">
        <div class="agrfuxd-header-content">
            <div class="agrfuxd-logo-section">
                <span class="dashicons dashicons-images-alt2"></span>
                <div>
                    <h1><?php esc_html_e('ACF Gallery & Repeater', 'advanced-gallery-repeater-fields-for-acf'); ?></h1>
                    <p class="agrfuxd-version">Version <?php echo esc_html(AGRFUXD_VERSION); ?> by UXD Experts</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="agrfuxd-content-grid">
        <!-- Left Column -->
        <div class="agrfuxd-main-column">
            <!-- Shortcodes Card -->
            <div class="agrfuxd-card">
                <div class="agrfuxd-card-header">
                    <span class="dashicons dashicons-shortcode"></span>
                    <h2><?php esc_html_e('Available Shortcodes', 'advanced-gallery-repeater-fields-for-acf'); ?></h2>
                </div>
                <div class="agrfuxd-card-body">
                    <!-- Gallery Shortcode -->
                    <div class="agrfuxd-shortcode-section">
                        <h3><?php esc_html_e('Gallery Shortcode', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <p><?php esc_html_e('Display an Enhanced Gallery field anywhere:', 'advanced-gallery-repeater-fields-for-acf'); ?></p>

                        <div class="agrfuxd-code-block">
                            <code>[agrfuxd_gallery field="your_field_name"]</code>
                            <button type="button" class="agrfuxd-copy-btn" data-copy='[agrfuxd_gallery field="your_field_name"]'>
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php esc_html_e('Copy', 'advanced-gallery-repeater-fields-for-acf'); ?>
                            </button>
                        </div>

                        <div class="agrfuxd-params">
                            <h4><?php esc_html_e('Parameters', 'advanced-gallery-repeater-fields-for-acf'); ?></h4>
                            <ul>
                                <li><code>field</code> <span class="required"><?php esc_html_e('required', 'advanced-gallery-repeater-fields-for-acf'); ?></span> - <?php esc_html_e('Field name', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><code>post_id</code> <span class="optional"><?php esc_html_e('optional', 'advanced-gallery-repeater-fields-for-acf'); ?></span> - <?php esc_html_e('Specific post ID', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                            </ul>
                        </div>

                        <div class="agrfuxd-examples">
                            <div class="agrfuxd-example">
                                <strong><?php esc_html_e('Basic:', 'advanced-gallery-repeater-fields-for-acf'); ?></strong>
                                <code>[agrfuxd_gallery field="project_gallery"]</code>
                            </div>
                            <div class="agrfuxd-example">
                                <strong><?php esc_html_e('With post ID:', 'advanced-gallery-repeater-fields-for-acf'); ?></strong>
                                <code>[agrfuxd_gallery field="project_gallery" post_id="123"]</code>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Gallery Layouts Card -->
            <div class="agrfuxd-card">
                <div class="agrfuxd-card-header">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <h2><?php esc_html_e('Gallery Layouts', 'advanced-gallery-repeater-fields-for-acf'); ?></h2>
                </div>
                <div class="agrfuxd-card-body">
                    <!-- Grid Layout -->
                    <div class="agrfuxd-shortcode-section">
                        <h3><?php esc_html_e('Grid Layout', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <p><?php esc_html_e('Responsive grid with customizable columns (1-6). Perfect for portfolios and product galleries.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                        <div class="agrfuxd-params">
                            <h4><?php esc_html_e('Features', 'advanced-gallery-repeater-fields-for-acf'); ?></h4>
                            <ul>
                                <li><?php esc_html_e('Equal height images', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Adjustable column count (1-6)', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Customizable gap spacing', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Built-in lightbox support', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="agrfuxd-divider"></div>

                    <!-- Masonry Layout -->
                    <div class="agrfuxd-shortcode-section">
                        <h3><?php esc_html_e('Masonry Layout', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <p><?php esc_html_e('Pinterest-style masonry layout with variable heights. Best for galleries with mixed aspect ratios.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                        <div class="agrfuxd-params">
                            <h4><?php esc_html_e('Features', 'advanced-gallery-repeater-fields-for-acf'); ?></h4>
                            <ul>
                                <li><?php esc_html_e('Variable height images', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Automatic column organization', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('No empty spaces', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Responsive breakpoints', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="agrfuxd-divider"></div>

                    <!-- Carousel Layout -->
                    <div class="agrfuxd-shortcode-section">
                        <h3><?php esc_html_e('Carousel / Slider Layout', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <p><?php esc_html_e('Touch-enabled slider with arrows, dots, and autoplay. Perfect for showcasing featured images.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                        <div class="agrfuxd-params">
                            <h4><?php esc_html_e('Features', 'advanced-gallery-repeater-fields-for-acf'); ?></h4>
                            <ul>
                                <li><?php esc_html_e('Autoplay with adjustable speed', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Navigation arrows', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Pagination dots', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Touch/swipe support', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Configurable slides per view', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="agrfuxd-divider"></div>

                    <!-- Justified Layout -->
                    <div class="agrfuxd-shortcode-section">
                        <h3><?php esc_html_e('Justified Layout', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <p><?php esc_html_e('Flickr-style justified rows with perfect alignment. Images maintain aspect ratio in evenly-spaced rows.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                        <div class="agrfuxd-params">
                            <h4><?php esc_html_e('Features', 'advanced-gallery-repeater-fields-for-acf'); ?></h4>
                            <ul>
                                <li><?php esc_html_e('Maintains aspect ratios', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Perfectly aligned rows', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Fixed row height', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                                <li><?php esc_html_e('Professional appearance', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="agrfuxd-notice notice-info">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php esc_html_e('Configure layouts in the field settings when editing your field group. All layouts work automatically with the_field() or shortcodes.', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Code Examples Card -->
            <div class="agrfuxd-card">
                <div class="agrfuxd-card-header">
                    <span class="dashicons dashicons-editor-code"></span>
                    <h2><?php esc_html_e('Template Code Examples', 'advanced-gallery-repeater-fields-for-acf'); ?></h2>
                </div>
                <div class="agrfuxd-card-body">
                    <div class="agrfuxd-code-example">
                        <h3><?php esc_html_e('Gallery with the_field()', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <pre><code>&lt;?php
// Auto-renders with configured layout
the_field('project_gallery');
?&gt;</code></pre>
                    </div>

                    <div class="agrfuxd-code-example">
                        <h3><?php esc_html_e('Gallery with Helper Function', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <pre><code>&lt;?php
// Manual rendering with full control
if (function_exists('agrfuxd_render_enhanced_gallery')) {
    echo agrfuxd_render_enhanced_gallery('gallery_field', get_the_ID());
}
?&gt;</code></pre>
                    </div>

                    <div class="agrfuxd-code-example">
                        <h3><?php esc_html_e('Repeater with have_rows()', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                        <pre><code>&lt;?php
// Using Enhanced Repeater with have_rows() and agrfuxd_get_sub_field()
if (have_rows('team_members')) :
    while (have_rows('team_members')) : the_row();
        $name = agrfuxd_get_sub_field('name');
        $photo = agrfuxd_get_sub_field('photo');

        echo '&lt;div class="member"&gt;';
        echo '&lt;h3&gt;' . esc_html($name) . '&lt;/h3&gt;';
        if ($photo) {
            echo wp_get_attachment_image($photo, 'medium');
        }
        echo '&lt;/div&gt;';
    endwhile;
endif;
?&gt;</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="agrfuxd-sidebar-column">
            <!-- Quick Links -->
            <div class="agrfuxd-card agrfuxd-card-accent">
                <div class="agrfuxd-card-header">
                    <span class="dashicons dashicons-admin-links"></span>
                    <h3><?php esc_html_e('Quick Links', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                </div>
                <div class="agrfuxd-card-body">
                    <ul class="agrfuxd-links-list">
                        <li>
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=acf-field-group')); ?>">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('Field Groups', 'advanced-gallery-repeater-fields-for-acf'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://uxdesignexperts.com" target="_blank" rel="noopener noreferrer">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e('UXD Experts Website', 'advanced-gallery-repeater-fields-for-acf'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://wordpress.org/plugins/advanced-gallery-repeater-fields-acf/" target="_blank" rel="noopener noreferrer">
                                <span class="dashicons dashicons-book"></span>
                                <?php esc_html_e('Documentation', 'advanced-gallery-repeater-fields-for-acf'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Elementor Integration -->
            <div class="agrfuxd-card">
                <div class="agrfuxd-card-header">
                    <span class="dashicons dashicons-welcome-widgets-menus"></span>
                    <h3><?php esc_html_e('Elementor Integration', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                </div>
                <div class="agrfuxd-card-body">
                    <ol class="agrfuxd-steps-list">
                        <li><?php esc_html_e('Add "Shortcode" widget', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                        <li><?php esc_html_e('Paste shortcode', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                        <li><?php esc_html_e('Update/Preview', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                    </ol>
                    <div class="agrfuxd-notice notice-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p><?php esc_html_e('Works in all Elementor templates!', 'advanced-gallery-repeater-fields-for-acf'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="agrfuxd-card agrfuxd-card-feature">
                <div class="agrfuxd-card-header">
                    <span class="dashicons dashicons-star-filled"></span>
                    <h3><?php esc_html_e('Features', 'advanced-gallery-repeater-fields-for-acf'); ?></h3>
                </div>
                <div class="agrfuxd-card-body">
                    <ul class="agrfuxd-features-list">
                        <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Works with ACF Free', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Gallery Layouts', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Repeater Fields', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Elementor Compatible', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Drag & Drop Sorting', 'advanced-gallery-repeater-fields-for-acf'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
