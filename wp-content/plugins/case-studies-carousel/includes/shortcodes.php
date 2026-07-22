<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render Case Studies Carousel via shortcode
 */
add_shortcode( 'render_case_study_carousel', function ( $atts ) {

    $atts = shortcode_atts(
        [
            'post_id' => get_the_ID(),
        ],
        $atts
    );

    $case_studies = case_study_carousel_get_items( (int) $atts['post_id'] );

    // Only keep items that have at least one field filled
    $case_studies = array_filter( $case_studies, function( $item ) {
        return (!empty(trim($item['logo'] ?? '')) 
                || !empty(trim($item['description'] ?? '')) 
                || !empty(trim($item['case_study_link'] ?? '')) );
    });

    if ( empty( $case_studies ) || ! is_array( $case_studies ) ) {
        return '';
    }

    $count     = count( $case_studies );
    $is_swiper = $count >= 3;

    ob_start();
    ?>

    <div class="case-studies__ctn <?php echo $is_swiper ? 'is-swiper' : 'is-grid'; ?>"
         data-count="<?php echo esc_attr( $count ); ?>">

        <?php if ( $is_swiper ) : ?>
            <div class="swiper case-studies-swiper">
                <div class="swiper-wrapper">
        <?php endif; ?>

        <?php foreach ( $case_studies as $case_study ) : 
            // Skip entirely if all fields are empty
            if ( empty($case_study['logo']) && empty($case_study['description']) && empty($case_study['case_study_link']) ) {
                continue;
            }
        ?>
            <div class="case-study-item <?php echo $is_swiper ? 'swiper-slide' : ''; ?>">
            <div class="case-study-item__logo">
                <?php if ( ! empty( $case_study['logo'] ) ) :
                    $logo_url = wp_get_attachment_image_url( $case_study['logo'], 'medium' );
                    if ( $logo_url ) : ?>
                       
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="">
                       
                    <?php endif;
                endif; ?>
 </div>
              <?php 
                        $description = trim($case_study['description'] ?? '');

                        if ($description !== '') : ?>
                            <div class="case-study-item__description">
                                <p><?php echo esc_html($description); ?></p>
                            </div>
                        <?php endif; ?>


                <?php 
                $link = trim( $case_study['case_study_link'] ?? '' );
                if ( $link !== '' ) : ?>
                    <div class="case-study-item__link">
                        <a href="<?php echo esc_url( $link ); ?>">
                            Read full case study
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <?php if ( $is_swiper ) : ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
});
