<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$related_cases = $data['posts'] ?? [];
$description   = $data['description'] ?? '';
$title  = $data['case_studies_title']; 

?>


<section class="related-case-studies">
    <div class="case-study-related-case-studies">
           
        <h2 class="case-study-related-case-studies-heading Archivo black"> 
            <?php if ( $title ) : ?>
                <?php echo esc_html( $title ); ?>
            <?php endif; ?>
        </h2>

        <?php if ( $description ) : ?>
            <p>
                <?php echo wp_kses_post( $description ); ?>
        </p>
        <?php endif; ?>

        <div class="related-case-studies__list">

            <?php foreach ( $related_cases as $post ) :
                setup_postdata( $post );

                $post_id = $post->ID;

                $client = get_field( 'client', $post_id );
                $client_name = $client['client_name'] ?? null;
                $logo   = $client['client_related_case_study_logo'] ?? null;
                
                $logo_banner = get_the_post_thumbnail_url($post_id, 'medium');

                $what_we_did = get_field( 'what_did_we_do', $post_id );

                $excerpt = $what_we_did
                    ? wp_trim_words( wp_strip_all_tags( $what_we_did ), 50, '...' )
                    : wp_trim_words( get_the_excerpt(), 50, '...' );
            ?>

            <article class="related-case-study">

            
                <?php if ( $logo_banner ) : ?>
                    <a class="related-case-study-logo-container" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                    
                        <div class="other-case-studies-second-logo-banner"> 

                                <img
                                    src="<?php echo esc_url( $logo_banner ); ?>"
                                    alt="<?php echo esc_attr( get_the_title($post_id) ); ?>"
                                    class="related-case-study__logo-banner"
                                >
                        </div> 
                            <?php if ( $logo ) : ?>
                                <div class="other-case-studies-second-logo"> 
                                    <img
                                        src="<?php echo esc_url( $logo['sizes']['medium'] ?? $logo['url'] ); ?>"
                                        alt="<?php echo esc_html( $client_name ); ?>"
                                        class="related-case-study__logo"
                                    >
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                        <div class="related-case-study-details"> 
                                <p class="related-case-study-excerpt"><?php echo esc_html( $excerpt ); ?></p> 
                                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                                        class="related-case-study__link">
                                        Read full case study
                                    </a>
                        </div>

            </article>

            <?php endforeach; ?>
        

        </div>
            <div class="related-case-study__read-all-case-studies center">
                    <a href="/our-work/">Read all case studies</a>
            </div>

    </div>
</section>

<?php wp_reset_postdata(); ?>