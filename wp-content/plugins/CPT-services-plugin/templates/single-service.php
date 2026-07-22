<?php
get_header();

while ( have_posts() ) : the_post();

    /**
     * ACF FIELDS
     */
    
    $group = get_field('our_service_detail_block');
    $our_service_title = $group['our_services_title'] ?? 'add title';
    $top_Banner = get_field('top_banner');
    $video      = $top_Banner['banner_video'] ?? null;
    $tagline    = $top_Banner['banner_tagline'] ?? '';
    $banner_video_poster = $top_Banner['banner_video_poster'] ?? null;
    $service_overview = get_field('service_overview_block');
    $key_services     = get_field('key_services_block');
    $detailed_service_block = get_field('detailed_service_block');
    $related_service_block = get_field('related_service_block') ;
 
?>
 <!-- Breadcrumb -->
  <div class="custom-post-header-breadcrumbs">
    <nav class="custom-post-header-breadcrumbs__nav" aria-label="Breadcrumb">
        <ul class="custom-post-header-breadcrumbs__list">

            <!-- Parent -->
            <li class="custom-post-header-breadcrumbs__item">
                <a href="<?php echo site_url('/services/'); ?>" class="custom-post-header-breadcrumbs__link">
                    <span class="custom-post-header-breadcrumbs__text">Services</span>
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

    <!-- Services page template -->

<div class="services-single">
    <div class="services-single-container">
  
<section class="services-title"> 
    <h1 class="services-title__heading Archivo black non-sentence-case">
        <?php the_title(); ?>
    </h1>

    <?php if ( $video ) : ?>
        <div class="services-title__video-banner">

            <video
                class="services-title__video"
                autoplay
                muted
                loop
                playsinline
                poster="<?php echo esc_url( is_array($banner_video_poster) ? $banner_video_poster['url'] : $banner_video_poster ); ?>"
            > 
                <source src="<?php echo esc_url( is_array($video) ? $video['url'] : $video ); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
                 

            <?php if ( $tagline ) : ?>
                <div class="case-study-featured-image-text Archivo semiBold">

               
                <p class="services-title__tagline">
                    <?php echo esc_html( $tagline ); ?>
                </p>
                 </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</section>
            </div>
            </div>
 <!-- =========================
        SERVICE OVERVIEW
    ========================== -->
<div class="services-overview-shoppers-search-today">
    <div class="services-overview-shoppers-search-today__container">
   
    <?php if ( $service_overview ) : ?>
           <?php  $section_class = 'service-overview';
            $image = $service_overview['overview_banner_image'] ?? null;
            if ( empty($image['ID']) ) {
                $section_class .= ' single';
            }
            ?>
    <section class="<?php echo esc_attr($section_class); ?> ">
           <?php
                                $image = $service_overview['overview_banner_image'] ?? null;

                                if ( ! empty( $image['ID'] ) ) : ?>
                                    <div class="services-overview-shoppers-search-today__overview_banner_image">
                                        <?php echo wp_get_attachment_image( $image['ID'], 'full' ); ?>
                                    </div>
                        <?php endif; ?>
        <div class="service-overview__inner">

            <?php if ( ! empty( $service_overview['overview_title'] ) ) : ?>
                <h2 class="Archivo black non-sentence-case">
                    <?php echo esc_html( $service_overview['overview_title'] ); ?>
                </h2>
                 <?php
                                $image = $service_overview['overview_banner_image'] ?? null;

                                if ( ! empty( $image['ID'] ) ) : ?>
                                    <div class="service-overview__inner_banner_image">
                                        <?php echo wp_get_attachment_image( $image['ID'], 'full' ); ?>
                                    </div>
                        <?php endif; ?>
            <?php endif; ?>

            <?php if ( ! empty( $service_overview['overview_content'] ) ) : ?>
                <div class="service-overview__content">
                    <?php
                        echo wp_kses_post(
                            wpautop( $service_overview['overview_content'] )
                        );
                    ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
    <?php endif; ?>
        </div>
 </div>
    <!-- =========================
        KEY SERVICES
    ========================== -->
                <div class="services-key-services"> 
                    <div class="services-key-services__inner-container"> 

                <?php if ( $key_services ) : ?>
                <section class="key-services">
                    <div class="key-services__inner">

                        <?php if ( ! empty( $key_services['key_services_title'] ) ) : ?>
                            <h2 class="Archivo black non-sentence-case">
                                <?php echo esc_html( $key_services['key_services_title'] ); ?>
                            </h2>
                        <?php endif; ?>

                        <?php if ( ! empty( $key_services['key_services_content'] ) ) : ?>
                            <div class="key-services__intro ">
                                <?php
                                    echo wp_kses_post(
                                        wpautop( $key_services['key_services_content'] )
                                    );
                                ?>
                            </div>
                        <?php endif; ?>

                      <?php
                            $services = $key_services['key_service_list'] ?? null;

                            if ( $services && ! empty($services['key_service_list_container']) ) :

                                $lists = $services['key_service_list_container'];
                            ?>

                            <div class="key-services__list">

                                <?php foreach ( $lists as $list ) : 

                                    $point = '';
                                    $icon  = null;

                                    foreach ( $list as $key => $value ) {

                                        // Get point (point_1, point_2, etc)
                                        if ( strpos($key, 'point_') === 0 ) {
                                            $point = $value;
                                        }

                                        // Get icon (empty key)
                                        if ( $key === '' ) {
                                            $icon = $value;
                                        }
                                    }

                                    if ( ! empty( $point ) ) :
                                ?>

                                    <div class="key-services__item">

                                        <span class="key-services__index">

                                            <?php if ( ! empty( $icon['ID'] ) ) : ?>

                                                <!-- Uploaded SVG -->
                                                <?php echo wp_get_attachment_image( $icon['ID'], 'full' ); ?>

                                            <?php else : ?>

                                                <!-- Default SVG -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
                                                    <path d="M0 15H12M12 15V30M12 15V0M30 15H18M18 15V30M18 15V0" stroke="#621EFF" stroke-width="2"/>
                                                </svg>

                                            <?php endif; ?>

                                        </span>

                                        <div class="key-services__text">
                                            <?php echo wp_kses_post( wpautop( $point ) ); ?>
                                        </div>

                                    </div>

                                <?php 
                                    endif;
                                endforeach; 
                                ?>

                            </div>

                            <?php endif; ?>

                        </div>
                </section>
                <?php endif; ?>
            </div>
            </div>
         <div class="services-SEO"> 
            <div class="services-SEO__container"> 
            <?php if ( $detailed_service_block ) : ?>
            <section class="services-SEO__inner-container"> 
                <div class="services-SEO__inner-container-wrapper">

            
                    <div class="services-SEO__inner-content">

                            <?php if ( ! empty( $detailed_service_block['service_title'] ) ) : ?>
                                <h2 class="Archivo black non-sentence-case">
                                    <?php echo esc_html( $detailed_service_block['service_title'] ); ?>
                                </h2>
                            <?php endif; ?>

                            <?php if ( ! empty( $detailed_service_block['service_content'] ) ) : ?>
                                <div class="key-services__intro">
                                    <?php echo wp_kses_post( wpautop( $detailed_service_block['service_content'] ) ); ?>
                                </div>
                            <?php endif; ?> 

                            <?php
                                $image = $detailed_service_block['detailed_block_image'] ?? null;

                                if ( ! empty( $image['ID'] ) ) : ?>
                                    <div class="detailed-service__image">
                                        <?php echo wp_get_attachment_image( $image['ID'], 'full' ); ?>
                                    </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                        $services_list = $detailed_service_block['service_list_block'] ?? null;
                        if ( $services_list ) :
                        ?>
                    
                        <div class="services-SEO__lists">
                            <?php foreach ( $services_list as $service ) : ?>

                                <?php
                                // Skip empty rows
                                if (
                                    empty( $service['title'] ) &&
                                    empty( $service['content'] ) &&
                                    empty( $service['icon'] )
                                ) {
                                    continue;
                                }

                                $icon = $service['icon'] ?? null;

                                    // If numeric, convert to array
                                    if ( is_numeric($icon) ) {
                                        $icon = [
                                            'ID' => $icon,
                                            'url' => wp_get_attachment_url($icon)
                                        ];
                                    }
                                $title   = $service['title'] ?? '';
                                $content = $service['content'] ?? '';
                                
                                                    ?>
                                                    
                     
                                <div class="services-SEO__lists-items">
                                         <div class="services-SEO__lists-items-icon">
                                                <?php if ($icon) : ?>
                                                <div class="services-SEO__lists-items__icon">
                                                    <span>
                                                        <?php
                                                        $svg_path = get_attached_file($icon['ID']);
                                                        if ($svg_path && file_exists($svg_path)) {
                                                            echo file_get_contents($svg_path);
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                    </div>
                                        <div class="services-SEO__lists-items-content">

                                            <?php if ( $title ) : ?>
                                                <div class="font_m">
                                                    <h3> 
                                                        <?php echo esc_html( $title ); ?>
                                                    </h3>
                                                </div>
                                            <?php endif; ?>
        
                                            <?php if ( $content ) : ?> 
                                                    <?php echo wp_kses_post( wpautop( $content ) ); ?> 
                                            <?php endif; ?>
                                        </div>

                                </div>

                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
            <?php endif; ?> 
                </div>
            </div>
        </div>
              
    <!-- call faq template here -->
                    <section class="services-faq">
                         <?php echo do_shortcode('[render_faq]'); ?>
                    </section>

    <!-- Related case studies Section -->
     <div class="services-case-study"> 
         <?php echo do_shortcode('[related_case_studies]'); ?>
     </div>
    <!-- Related service block -->
         <?php
        $related_services = function_exists('sp_get_related_services')
            ? sp_get_related_services( get_the_ID(), 4 )
            : [];
        ?>

        <?php if ( ! empty( $related_services ) ) : ?>
        <div class="related-services center">
            <div class="related-services__inner-content">
    <div class="related-services__title Archivo black non-sentence-case">

        <h2>Explore our other services</h2>
    </div>
         <div class="related-services_lists"> 
             <?php foreach ( $related_services as $service ) : ?>
                         <a href="<?php echo esc_url( get_permalink($service->ID) ); ?>" class="related-service-item-link">

                                <div class="related-service-item">
                                    <div class="related-services_list__title font_m">
                                    <?php
                                            $group = get_field('our_service_detail_block', $service->ID);
                                            $our_service_title = $group['our_services_title'] ?? '';

                                            if (!$our_service_title) {
                                                $our_service_title = get_the_title($service->ID);
                                            }
                                            ?>

                                            <h3 class="related-service-item__title">
                                                <?php echo esc_html($our_service_title); ?>
                                            </h3>
                                    </div>
                
                                        <?php
                                        $types = function_exists('sp_get_service_types')
                                            ? sp_get_service_types( $service->ID, 4 )
                                            : [];
                                        ?>
                                    <?php if ( ! empty( $types ) ) : ?>
                                        <?php $term = reset( $types ); ?>
                                        <div class="related-service-item__types">
                                            <span class="related-service-item__type">
                                                <?php echo esc_html( $term->name ); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?> 
                
                                </div>
                    </a>
                
             <?php endforeach; ?>
         </div>
                <div class="related-services__see-all-services center">
                    <a href="<?php echo site_url('/services/'); ?>">See all services</a>
                </div>
                  </div>
        </div>
<?php endif; ?>

<?php
endwhile;

get_footer();
