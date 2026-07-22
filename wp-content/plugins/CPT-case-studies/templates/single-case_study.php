<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) : the_post();

    // Client Group
    $client = get_field('client');
    $banner_subtitle = get_field('banner_subtitle', get_the_ID());
    // Results Group
    $results = get_field('results');
?>
 <!-- Breadcrumb -->
  <div class="custom-post-header-breadcrumbs">
    <nav class="custom-post-header-breadcrumbs__nav" aria-label="Breadcrumb">
        <ul class="custom-post-header-breadcrumbs__list">

            <!-- Parent -->
            <li class="custom-post-header-breadcrumbs__item">
                <a href="<?php echo esc_url( site_url( '/our-work/' ) ); ?>" class="custom-post-header-breadcrumbs__link">
                    <span class="custom-post-header-breadcrumbs__text">Our work</span>
                </a>
            </li>

            <!-- Separator -->
            <li class="custom-post-header-breadcrumbs__separator">
                <span class="custom-post-header-breadcrumbs__separator-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="6" viewBox="0 0 9 6" fill="none">
                        <path d="M5.87203 5.6565L5.16653 4.9475L6.80153 3.32L-0.000969078 3.3135L3.13701e-05 2.3135L6.78403 2.32L5.17653 0.705L5.88503 -1.23354e-07L8.70703 2.835L5.87203 5.6565Z" fill="#621EFF"></path>
                    </svg>
                </span>
            </li>

            <!-- Current -->
            <li class="custom-post-header-breadcrumbs__item custom-post-header-breadcrumbs__item--current">
                <span class="custom-post-header-breadcrumbs__text" aria-current="page">
                    <?php the_title(); ?>
                </span>
            </li>

        </ul>
    </nav>
</div>



<div class="case-study-single">

   <div class="case-study-title-section">
     <!-- Title -->
    <h1 class="case-study-title Archivo black non-sentence-case"><?php the_title(); ?></h1>
       <div class="case-study-description-content">

        <?php the_content() ?>
</div>
   </div>

    <!-- Client Section -->
    <?php if ( $client ) : ?>
        <section class="case-study-client">

            <?php if ( has_post_thumbnail() ) : ?>
                <div class="case-study-featured-image">
                    <?php the_post_thumbnail( 'large' ); ?>
                        <div class="case-study-featured-image-text Archivo semiBold">
                            <p><?php echo esc_html( $banner_subtitle ); ?></p>
                        </div>
                </div>
            <?php endif; ?>

            <div class="case-study-featured-story-section">
                <div class="case-study-featured-inner-section">
                    <div class="case-study-featured-story-content">
                        <h2 class="Archivo black non-sentence-case">The Story</h2>
                        <div class="case-study-featured-story-container"> 
                            <div class="case-study-featured-story-paragraph"> 
                                <?php if ( ! empty( $client['client_overview'] ) ) : ?>
                                    <p><?php echo esc_html( $client['client_overview'] ); ?></p>
                                <?php endif; ?>
                            </div>

                             <div class="case-study-featured-story-logos">
                                <?php if ( $client['client_story_logo'] ) : ?>
                                    <img
                                        src="<?php echo esc_url( $client['client_story_logo']['sizes']['large'] ?? $client['client_story_logo']['url'] ); ?>"
                                        alt="<?php echo esc_html( $client['client_name'] ); ?>"
                                        class="case-study-featured-story__logo"
                                    >
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>
                    
                </div> 
            </div>
        </section>
    <?php endif; ?>

    <!-- What Did We Do -->
    <?php if ( get_field('what_did_we_do') ) : ?>
        <section class="case-study-work ">
            <div class="case-study-work-content">
                <h2 class="Archivo black non-sentence-case">What Did We Do?</h2>
               <div class="case-study-wpr-content__description"> 
                   <?php the_field('what_did_we_do'); ?>
               </div> 
                
            </div>
        </section>
    <?php endif; ?>

    <!-- Results Section -->
    <?php if ( $results ) : ?>
        <section class="case-study-results"> 
            <div class="case-study-resutls-section">
                <h2 class="Archivo black">The results</h2>
                  <?php if ( ! empty( $results['result_overview'] ) ) : ?>
                <div class="result-overview">
                    <?php echo wp_kses_post( $results['result_overview'] ); ?>
                </div>
            <?php endif; ?>
          
          

            <?php
            $metrics = $results['performance_metrics'] ?? null;
            if ( $metrics ) :
            ?>

                <div class="performance-metrics">
                        <ul>
                            <?php if ( $metrics['metric_1'] && $metrics['value_1'] ) : ?>
                                <li class="metric-item">
                                    <h3 class="metric-value Archivo_Thin">
                                        <span class="metric-count">
                                            <?php echo esc_html($metrics['value_1']); ?>
                                        </span>
                                    </h3>
                                    <span class="metric-label Archivo_light">
                                        <?php echo esc_html($metrics['metric_1']); ?>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ( $metrics['metric_2'] && $metrics['value_2'] ) : ?>
                                <li class="metric-item">
                                    <h3 class="metric-value Archivo_Thin">
                                        <span class="metric-count">
                                            <?php echo esc_html($metrics['value_2']); ?>
                                        </span>
                                    </h3>
                                    <span class="metric-label Archivo_light">
                                        <?php echo esc_html($metrics['metric_2']); ?>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ( $metrics['metric_3'] && $metrics['value_3'] ) : ?>
                                <li class="metric-item">
                                    <h3 class="metric-value Archivo_Thin">
                                        <span class="metric-count">
                                            <?php echo esc_html($metrics['value_3']); ?>
                                        </span>
                                    </h3>
                                    <span class="metric-label Archivo_light">
                                        <?php echo esc_html($metrics['metric_3']); ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

            <?php endif; ?>
  </div>
        </section>
    <?php endif; ?>
    <!-- call faq template here -->
        <section class="case-study-faq FAQ-section-elementor">
                <?php echo do_shortcode('[render_faq]');?>
        </section>

    <!-- Related case studies Section -->
    <?php echo do_shortcode('[related_case_studies]'); ?>
   

</div>

<?php
endwhile;
get_footer();
