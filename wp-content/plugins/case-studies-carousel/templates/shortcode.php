<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render Case Studies Carousel via shortcode
 */
add_shortcode( 'related_case_study_carousel', function ( $atts ) {

    $atts = shortcode_atts(
        [
            'post_id' => get_the_ID(),
        ],
        $atts
    );

    $post_id = (int) $atts['post_id'];

    //  Get ACF group
    $group   = get_field( 'related_case_studies_group', $post_id );
    $related = $group['related_case_studies'] ?? [];

    $related_ids = [];

    //  Collect selected posts
    if ( ! empty( $related ) ) {
        foreach ( $related as $p ) {
            if ( $p instanceof WP_Post && $p->post_status === 'publish' ) {
                $related_ids[] = $p->ID;
            }
        }
    }

    // Remove current post
    $related_ids = array_diff( $related_ids, [ $post_id ] );

    //  If selected → use them, else fallback
    if ( ! empty( $related_ids ) ) {

        $posts = get_posts([
            'post_type'      => 'case_study',
            'post__in'       => $related_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => -1,
        ]);

    } else {

        $posts = get_posts([
        'post_type'      => 'case_study',
        'posts_per_page' => 2,
        'post__not_in'   => [ $post_id ],
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    }

    if ( empty( $posts ) ) {
        return '';
    }

    $count     = count( $posts );
    $is_swiper = $count >= 3;

    ob_start();
    ?>

    <div class="case-studies__ctn <?php echo $is_swiper ? 'is-swiper' : 'is-grid'; ?>"
         data-count="<?php echo esc_attr( $count ); ?>">

        <?php if ( $is_swiper ) : ?>
            <div class="swiper case-studies-swiper related">
                <div class="swiper-wrapper">
        <?php endif; ?>

        <?php foreach ( $posts as $post ) :

            if ( ! $post instanceof WP_Post ) continue;

            $pid = $post->ID;

            $client = get_field( 'client', $pid );
            $logo   = $client['client_related_case_study_logo'] ?? null;

            $what_we_did = get_field( 'what_did_we_do', $pid );

            $description = $what_we_did
                ? wp_trim_words( wp_strip_all_tags( $what_we_did ), 30, '...' )
                : wp_trim_words( get_the_excerpt( $pid ), 30, '...' );

            $link = get_permalink( $pid );

            // Skip empty
            if ( empty($logo) && empty($description) && empty($link) ) {
                continue;
            }
        ?>

            <div class="case-study-item <?php echo $is_swiper ? 'swiper-slide' : ''; ?>">

                <!-- LOGO -->
                <?php if ( ! empty( $logo ) ) :
                    $logo_url = is_array($logo)
                        ? ($logo['sizes']['medium'] ?? $logo['url'])
                        : wp_get_attachment_image_url( $logo, 'medium' );

                    if ( $logo_url ) : ?>
                        <div class="case-study-item__logo">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="">
                        </div>
                    <?php endif;
                endif; ?>

                <!-- DESCRIPTION -->
                <?php if ( ! empty( $description ) ) : ?>
                    <div class="case-study-item__description">
                        <p><?php echo esc_html( $description ); ?></p>
                    </div>
                <?php endif; ?>

                <!-- LINK -->
                <?php if ( ! empty( $link ) ) : ?>
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