<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render FAQ accordion
 * Usage: [render_faq]
 */
add_shortcode( 'render_faq', function ( $atts ) {
  // Enqueue assets HERE
    wp_enqueue_style(
        'faq-render-css',
        FAQ_PLUGIN_URL . 'assets/css/faqs-render.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'faq-render-js',
        FAQ_PLUGIN_URL . 'assets/js/faq-render.js',
        [],
        '1.0',
        true
    );

    $atts = shortcode_atts(
        [
            'post_id' => get_the_ID(),
        ],
        $atts
    );

    $faqs = faq_get_page_faqs( (int) $atts['post_id'] );

    if ( empty( $faqs ) ) {
        return '';
    }

    ob_start();
    ?>
    <section class="faq-block">
        <h2 class="faq-block__heading Archivo black">Frequently asked</h2>
        <div class="faq-block__accordion">
            <?php foreach ( $faqs as $index => $faq ) :

                $faq_id       = $faq->ID;
                $number       = $index + 1;
                $question     = get_field( 'faq_question', $faq_id ) ?: get_the_title( $faq_id );
                $answer       = get_field( 'faq_answer', $faq_id );

                if ( ! $question || ! $answer ) {
                    continue;
                }
                ?>
                <div class="faq-block__item">
                    <button
                        class="faq-block__question"
                        aria-expanded="false"
                        aria-controls="faq-<?php echo esc_attr( $faq_id ); ?>"
                    >
                        <?php echo esc_html( $number ) . '. ' . esc_html( $question ); ?>
                        <div class="accordion-item-title-icon">
                            <span class="accordion-open-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                            <span class="accordion-close-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </div>
                         
                      
                    </button>

                    <div
                        id="faq-<?php echo esc_attr( $faq_id ); ?>"
                        class="faq-block__answer"
                        
                    >
                        <?php echo wp_kses_post( $answer ); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php

    return ob_get_clean();
} );
