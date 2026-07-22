<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// 1. Featured case study
$featured_query = new WP_Query([
    'post_type'      => 'case_study',
    'posts_per_page' => 1,
    'tax_query'      => [
        [
            'taxonomy' => 'post_tag', 
            'field'    => 'slug',
            'terms'    => 'featured',
        ],
    ],
]);

$featured_id = null;
?>

<div class="case-studies-grid">

<?php if ($featured_query->have_posts()) : 
    $featured_query->the_post();
    $featured_id = get_the_ID();
?>  
    <div class="case-study-card case-study-node featured">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="case-study-featured-image node-featured-image">
                 <a class="case-study-featured-link" href="<?php the_permalink(); ?>"> 
                <?php the_post_thumbnail( 'large' ); ?></a>
                    
            </div>
        <?php endif; ?>
         <!--staticly adding the banner sub title-->
<p class="node-success-story">
    <?php
    $terms = get_the_terms( get_the_ID(), 'story-category' );

    if ( $terms && ! is_wp_error( $terms ) ) {
        echo esc_html( $terms[0]->name ); // first term only
    } else {
        echo 'Success story';
    }
    ?>
</p> <a class="case-study-featured-link" href="<?php the_permalink(); ?>"> 
        <h2 class="Archivo black"><?php the_title(); ?></h2> </a>
        <p class="excerpt"><?php echo esc_html(get_the_excerpt());?></p>   
        <a class="read-more"  href="<?php the_permalink(); ?>">Read more</a>
    </div>
<?php endif; wp_reset_postdata(); ?>


<?php
// 2. Latest 4 excluding featured
$latest_query = new WP_Query([
    'post_type'      => 'case_study',
    'posts_per_page' => 4,
    'post__not_in'   => $featured_id ? [$featured_id] : [],
    'tax_query'      => [
        [
            'taxonomy' => 'story-category',
            'operator' => 'EXISTS',
        ],
    ],
]);

if ($latest_query->have_posts()) :
    while ($latest_query->have_posts()) : $latest_query->the_post(); ?>
        <div class="case-study-card case-study-node not-featured">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="case-study-featured-image node-featured-image">
                     <a class="case-study-featured-link" href="<?php the_permalink(); ?>">  
                         <?php the_post_thumbnail( 'large' ); ?>
                     </a>
                      
                </div>
            <?php endif; ?>
            <!--staticly adding the banner sub title-->
            <?php
                    $terms   = get_the_terms(get_the_ID(), 'story-category');
                    $title   = get_the_title();
                    $excerpt = get_the_excerpt();
                    $link    = get_the_permalink();

                    /* Check if ANY content exists */
                    if (
                        (!empty($terms) && !is_wp_error($terms)) ||
                        !empty($title) ||
                        !empty($excerpt)
                    ) :
                    ?>
                    <div class="case-study-card-content-block">

                        <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                            <p class="node-success-story">
                                <?php echo esc_html($terms[0]->name); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($title)) : ?>
                            <a class="case-study-featured-link" href="<?php echo esc_url($link); ?>">
                                <h3 class="Archivo black"><?php echo esc_html($title); ?></h3>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($excerpt)) : ?>
                            <p class="excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>

                        <a class="read-more" href="<?php echo esc_url($link); ?>">Read more</a>

                    </div>
                    <?php endif; ?>
        </div>
    <?php endwhile;
endif;

wp_reset_postdata();
?>

</div>
