<?php
get_header();

while ( have_posts() ) : the_post();

    /**
     * ACF FIELDS
     */
    
    $group = get_field('ai-suite_detail_block');
    $our_service_title = $group['our_services_title'] ?? 'add title';
  
    $service_overview = get_field('ai-suite_overview_block');
    $key_services     = get_field('key_services_block');
    $detailed_service_block = get_field('ai-suite_benefits_and_outcomes'); 
      
?>
 <!-- Breadcrumb -->
  <div class="custom-post-header-breadcrumbs">
    <nav class="custom-post-header-breadcrumbs__nav" aria-label="Breadcrumb">
        <ul class="custom-post-header-breadcrumbs__list">

            <!-- Parent -->
            <li class="custom-post-header-breadcrumbs__item">
                <a href="<?php echo site_url('/ai-suite/'); ?>" class="custom-post-header-breadcrumbs__link">
                    <span class="custom-post-header-breadcrumbs__text">AI suite </span>
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
                    <?php the_title();   ?>
                </span>
            </li>

        </ul>
    </nav>
</div>

    <!-- Services page template -->

<div class="ai-suite-services-single ">
    <div class="ai-suite-single-container">
  
<section class="ai-suite-title"> 
    <h1 class="ai-suite-title__heading Archivo black non-sentence-case">
        <?php the_title(); ?>
    </h1>
       <?php if ( has_post_thumbnail() ) : ?>
                <div class="ai-suite-featured-image">
                    <?php the_post_thumbnail( 'large' ); ?>
                        <div class="ai-suite-featured-image-text Archivo semiBold"> 
                                    <?php the_excerpt(); ?>
                                
                        </div>
                </div>
            <?php endif; ?>
    

</section>
            </div>
            </div>
 <!-- =========================
        SERVICE OVERVIEW
    ========================== -->
<div class="ai-suite-overview-shoppers-search-today">
    <div class="ai-suite-overview-shoppers-search-today__container">
   
    <?php if ( $service_overview ) : ?>
          <?php  $section_class = 'ai-suite-overview';
            $image = $service_overview['overview_banner_image'] ?? null;
            if ( empty($image['ID']) ) {
                $section_class .= ' single';
            }
            ?>
    <section class="<?php echo esc_attr($section_class); ?>">
            <?php
                    $image = $service_overview['overview_banner_image'] ?? null;

                    if ( ! empty( $image['ID'] ) ) : ?>
                        <div class="services-overview-shoppers-search-today__overview_banner_image">
                            <?php echo wp_get_attachment_image( $image['ID'], 'full' ); ?>
                        </div>
            <?php endif; ?>
        <div class="ai-suite-overview__inner">

            <?php if ( ! empty( $service_overview['overview_title'] ) ) : ?>
                <h2 class="Archivo black non-sentence-case">
                    <?php echo esc_html( $service_overview['overview_title'] ); ?>
                </h2>
                <?php
                                $image = $service_overview['overview_banner_image'] ?? null;

                                if ( ! empty( $image['ID'] ) ) : ?>
                                    <div class="ai-suite-overview__inner_banner_image">
                                        <?php echo wp_get_attachment_image( $image['ID'], 'full' ); ?>
                                    </div>
                        <?php endif; ?>
            <?php endif; ?>

            <?php if ( ! empty( $service_overview['overview_content'] ) ) : ?>
                <div class="ai-suite-overview__content">
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
                    <div class="services-ai-suite-key-services__inner-container"> 

                <?php if ( $key_services ) : ?>
                <section class="ai-suite-key-services">
                    <div class="ai-suite-key-services__inner">

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
                        ?>

                        <div class="key-services__list">

                            <?php foreach ( $services['key_service_list_container'] as $service ) : ?>

                                <?php
                                $point = '';
                                $icon  = $service['icon'] ?? null;

                                // Get point (point_1, point_2, point_3...)
                                foreach ( $service as $key => $value ) {
                                    if ( strpos($key, 'point_') === 0 ) {
                                        $point = $value;
                                    }
                                }
                                ?>

                                <?php if ( ! empty( $point ) ) : ?>
                                    <div class="key-services__item">

                                        <span class="key-services__index"> 

                                            <?php if ( ! empty( $icon ) ) : ?>

                                                <?php
                                                // if icon is ID (your case)
                                                if ( is_numeric($icon) ) {
                                                    $svg_path = get_attached_file($icon);

                                                    if ( $svg_path && file_exists($svg_path) ) {
                                                        echo file_get_contents($svg_path); // inline SVG
                                                    } else {
                                                        echo wp_get_attachment_image($icon, 'full');
                                                    }
                                                }
                                                ?>

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
                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                        <?php endif; ?>

                    </div>
                </section>
                <?php endif; ?>
            </div>
            </div>
         <div class="ai-suite-services-SEO"> 
            <div class="ai-suite-services-SEO__container"> 
            <?php if ( $detailed_service_block ) : ?>
            <section class="services-SEO__inner-container"> 
                <div class="services-SEO__inner-container-wrapper">

            
                    <div class="ai-suite-services-SEO__inner-content">

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
              
  
    <!-- Related case studies Section -->
    <div class="ai-suite-case-study"> 
            <?php echo do_shortcode('[related_case_studies]'); ?>
        </div>
        <!-- Related service block -->
            <?php
                $related_services = function_exists('sp_get_related_services')
                    ? sp_get_related_services( get_the_ID(), 4 )
                    : [];
            ?>

       

  <!-- call faq template here -->
                    <section class="ai-suite-faq">
                         <?php echo do_shortcode('[render_faq]');
 ?>
                    </section>

<?php
endwhile;

get_footer();
