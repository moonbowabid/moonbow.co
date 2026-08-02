<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Current post
$current_post_id = get_the_ID();

// Get current post category
$terms = wp_get_post_terms( $current_post_id, 'help_category' );
$current_term = ! empty( $terms ) ? $terms[0] : null;
?>
<!-- breadcrumbs -->
  <div class="custom-post-header-breadcrumbs">
    <nav class="custom-post-header-breadcrumbs__nav" aria-label="Breadcrumb">
        <ul class="custom-post-header-breadcrumbs__list">

            <!-- Parent -->
            <li class="custom-post-header-breadcrumbs__item">
                <a href="<?php echo esc_url( site_url( '/help-centre/' ) ); ?>" class="custom-post-header-breadcrumbs__link">
                    <span class="custom-post-header-breadcrumbs__text">Help centre</span>
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
<div class="help-single-layout">

    <!-- Search -->
     <div class="help-center-custom-post-title">
        <h1 class="Archivo black">
            Help centre
        </h1>
    </div>
  
     <!-- <a class="help-single__back" href="<?php echo esc_url( site_url( '/help-centre/' ) ); ?>"> -->
            
            <!-- IM Closed it for Help Search Case No.CI-050<div class="help-center-search-container">
           <form
                action="<?php echo esc_url( site_url('/help-centre/') ); ?>"
                method="get"
                class="help-center-search"
                >
                <button  class="search-icon-button" action="<?php echo esc_url( site_url('/help-centre/') ); ?>"> 
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none"><script xmlns=""/>
                    <path d="M38.9692 40.3071L26.4452 27.7831C25.4452 28.6351 24.2952 29.2944 22.9952 29.7611C21.6952 30.2278 20.3886 30.4611 19.0752 30.4611C15.8726 30.4611 13.1619 29.3524 10.9432 27.1351C8.72457 24.9178 7.61523 22.2078 7.61523 19.0051C7.61523 15.8024 8.72323 13.0911 10.9392 10.8711C13.1552 8.65111 15.8646 7.53978 19.0672 7.53711C22.2699 7.53445 24.9819 8.64378 27.2032 10.8651C29.4246 13.0864 30.5352 15.7978 30.5352 18.9991C30.5352 20.3884 30.2892 21.7331 29.7972 23.0331C29.3052 24.3331 28.6586 25.4451 27.8572 26.3691L40.3812 38.8911L38.9692 40.3071ZM19.0772 28.4591C21.7306 28.4591 23.9706 27.5458 25.7972 25.7191C27.6239 23.8924 28.5372 21.6518 28.5372 18.9971C28.5372 16.3424 27.6239 14.1024 25.7972 12.2771C23.9706 10.4518 21.7306 9.53845 19.0772 9.53711C16.4239 9.53578 14.1832 10.4491 12.3552 12.2771C10.5272 14.1051 9.6139 16.3451 9.61523 18.9971C9.61657 21.6491 10.5299 23.8891 12.3552 25.7171C14.1806 27.5451 16.4206 28.4584 19.0752 28.4571" fill="#621EFF"/>
                </svg> 
            </button>
                    <input
                        type="text"
                        name="q"
                        class="help-center-search__input single-help-page-search"
                        placeholder="Search"
                         autocomplete="off"
                    >
                     <div class="help-search-hint"  >
                        Press Enter to search
                    </div>
                </form> 
    </div>-->


    <div class="help-single-main-container">

        <!-- LEFT SIDEBAR -->
        <aside class="help-single-sidebar">

            <?php if ( $current_term ) : ?>
                <h3 class="help-single-sidebar__title">
                    <?php echo esc_html( $current_term->name ); ?>
                </h3>

                <?php
                $sidebar_query = new WP_Query([
                    'post_type'      => 'help-center',
                    'posts_per_page' => -1,
                    'orderby'        => 'date',
                    'order'          => 'ASC',
                    'tax_query'      => [[
                        'taxonomy' => 'help_category',
                        'field'    => 'term_id',
                        'terms'    => $current_term->term_id,
                    ]],
                ]);
                ?>

                <?php if ( $sidebar_query->have_posts() ) : ?>
                    <ul class="help-center__category-list">
                        <?php while ( $sidebar_query->have_posts() ) : $sidebar_query->the_post(); ?>
                            <li class="help-center__category-item">
                                <a
                                    href="<?php the_permalink(); ?>"
                                    class="help-center__category-link <?php echo get_the_ID() === $current_post_id ? 'is-active' : ''; ?>"
                                >
                                    <?php the_title(); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

        </aside>

        <!-- RIGHT CONTENT -->
        <main class="help-single-content">
 
            <h1 class="help-single__title">
                <?php the_title(); ?>
            </h1>

            <div class="help-single__content">
                <?php the_content(); ?>
            </div>
            
            <?php
/**
 * RELATED CASE STUDIES (Help Center)
 */

// ACF group
$related_group = get_field( 'help_center_related_case_studies_group', $current_post_id );

// Relationship field
$selected_cases = $related_group['related_case_studies'] ?? [];

// Decide source
if ( ! empty( $selected_cases ) ) {
    $related_cases = $selected_cases;
} 

?>

<?php if ( ! empty( $related_cases ) ) : ?>
<div class="help-center-related-case-studies">
<section class="related-case-studies">
    <div class="case-study-related-case-studies">

        <div class="related-case-studies__list">
            <div class="help-center-related-case-title Archivo black">
                <h3>Examples</h3>
            </div>
            <?php foreach ( $related_cases as $post ) :
                setup_postdata( $post );

                $post_id = $post->ID;

                $client = get_field( 'client', $post_id );
                $logo   = $client['client_logo'] ?? null;
                $client_name = $client['client_name'] ?? null;
                $logo_banner=$client['mini_logo']?? null;

                $what_we_did = get_field( 'what_did_we_do', $post_id );

                $excerpt = $what_we_did
                    ? wp_trim_words( wp_strip_all_tags( $what_we_did ), 50, '...' )
                    : wp_trim_words( get_the_excerpt(), 50, '...' );
            ?>

            <article class="related-case-study">
  <?php if ( $logo_banner ) : ?>
                    <div class="related-case-study-logo-container">
                    
                <div class="other-case-studies-second-logo-banner"> 

                    <img
                        src="<?php echo esc_url( $logo_banner['sizes']['medium'] ?? $logo_banner['url'] ); ?>"
                        alt="<?php echo esc_html( $client_name ); ?>"
                        class="related-case-study__logo-banner"
                    >
                </div>
                    <div class="other-case-studies-second-logo"> 
                        <img
                          src="<?php echo esc_url( $logo['sizes']['medium'] ?? $logo['url'] ); ?>"
                          alt="<?php echo esc_html( $client_name ); ?>"
                          class="related-case-study__logo"
                      >
                  </div>
                    </div>
                <?php endif; ?>

                <div class="related-case-study-details">
                    <p class="related-case-study-excerpt">
                        <?php echo esc_html( $excerpt ); ?>
                    </p>

                    <a
                        href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                        class="related-case-study__link"
                    >
                        Read full case study
                    </a>
                </div>

            </article>

            <?php endforeach; ?>

        </div>

       

    </div>
</section>
</div>

<?php wp_reset_postdata(); ?>
<?php endif; ?>


        </main>

    </div>

</div>

<?php get_footer(); ?>
