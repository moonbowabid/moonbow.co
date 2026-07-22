<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$ai_suite_query = new WP_Query([
    'post_type'      => 'ai-suite',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    // 'post__not_in'   => $reverse_ids
]);

if ( $ai_suite_query->have_posts() ) : ?>

<div class="ai-suite-services-grid">

        <?php while ( $ai_suite_query->have_posts() ) :
            $ai_suite_query->the_post();
            $post_id = get_the_ID();

            $group = get_field('ai-suite_detail_block', $post_id);
            $ai_suite_service_title = $group['our_services_title'] ?? '';
            if(!$ai_suite_service_title){
                $ai_suite_service_title = get_the_title();
            }

            $permalink = get_permalink($post_id);
        ?>

        <div class="ai-suite-services-grid__list">
            <a href="<?php echo esc_url($permalink); ?>"> 
                <div>
                    <div class="ai-suite-read-more-title" >
                        <h3 class="service-title Archivo extrabold">
                            <?php echo esc_html($ai_suite_service_title); ?>
                        </h3>
                    </div>

                    <div class="service-content">
                        <?php the_content(); ?>
                    </div>

                    <div class="service-read-more">
                        <span>Read more</span> 
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3"></path>
                                        </svg>
                    </div>
                </div>
        </a>

        </div>

    <?php endwhile; ?>

</div>

<?php endif;

wp_reset_postdata();
?>