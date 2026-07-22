<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$services_query = new WP_Query([
    'post_type'      => 'service',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    // 'post__not_in'   => $reverse_ids
]);

if ( $services_query->have_posts() ) : ?>

<div class="our-services-grid">

        <?php while ( $services_query->have_posts() ) :
            $services_query->the_post();
            $post_id = get_the_ID();

            $group = get_field('our_service_detail_block', $post_id);
            $our_service_title = $group['our_services_title'] ?? '';
            if(!$our_service_title){
                $our_service_title = get_the_title();
            }

            $permalink = get_permalink($post_id);
        ?>

        <div class="our-services-grid__list" >
            <a href="<?php echo esc_url($permalink); ?>">
                <div>
                <div class="service-read-more-title" >
                                <h3 class="service-title Archivo extrabold">
                                    <?php echo esc_html($our_service_title); ?>
                                </h3>
                            </div>

                            <div class="service-content">
                                <?php the_excerpt(); ?>
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