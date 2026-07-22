<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode('render_jobs', function () {

    ob_start();

    $jobs = new WP_Query([
        'post_type'      => 'job',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        
    ]);

    if ($jobs->have_posts()) :

        echo '<div class="jobs-wrapper">';

        while ($jobs->have_posts()) : $jobs->the_post();

            $location   = get_field('job_location');
 
            ?>
<div class="career-jobs">
   
    <div class="career-jobs__card">

        <!-- Department -->
        <?php
        $terms = get_the_terms(get_the_ID(), 'job-department');
        if ($terms && !is_wp_error($terms)) :
        ?>
            <div class="career-jobs__department">
                <span class="career-jobs__department-label">
                    <?php echo esc_html($terms[0]->name); ?>
                </span>
            </div>
        <?php endif; ?>
            <div class="career-jobs__inner">


                    <div class="career-jobs__header">

                        <div class="career-jobs__title-wrapper">
                            <h3 class="career-jobs__title Archivo black">
                                <?php the_title(); ?>
                            </h3>
                        </div>

                        <!-- Meta Information -->
                        <div class="career-jobs__meta">
                            <?php
                                    $timings = get_the_terms(get_the_ID(), 'job-type');

                                    if ($timings && !is_wp_error($timings)) :

                                        $timing_names = wp_list_pluck($timings, 'name');
                                    ?>

                                    <div class="career-jobs__meta-item career-jobs__meta-item--timing">
                                        <img 
                                            decoding="async"
                                            src="<?php echo esc_url(CPT_JOBS_URL . 'assets/images/time.svg'); ?>" 
                                            alt="Time Icon"
                                            class="career-jobs__icon"
                                        >

                                        <span class="career-jobs__meta-item--timing-label">
                                            <?php echo esc_html(implode(' | ', $timing_names)); ?>
                                        </span>
                                    </div>

                            <?php endif; ?>


                            <?php if ($location) : ?>
                                <div class="career-jobs__meta-item career-jobs__meta-item--location">
                                     <img 
                                    decoding="async"
                                    src="<?php echo esc_url( CPT_JOBS_URL . 'assets/images/Location.svg' ); ?>" 
                                    alt="Location Icon"
                                    class="career-jobs__icon"
                                >
                                <span class="career-jobs__meta-item career-jobs__meta-item--location-label"> 
                                    <?php echo esc_html($location); ?>
                                </span>
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- Description -->
                    <div class="career-jobs__body">
                        <div class="career-jobs__description">
                               <?php the_content(); ?>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="career-jobs__footer"> 
                             <a class="career-jobs__apply-btn"
                            href="#"
                            data-job-location="<?php echo esc_attr($location); ?>"
                            data-job-timing="<?php echo esc_attr(implode(' | ', $timing_names)); ?>"
                            data-job-title="<?php echo esc_attr(get_the_title()); ?>"
                                data-job-department="<?php echo esc_attr($terms[0]->name); ?>"
                            >
                                Apply now
                            </a>
                    </div>
            </div>
    </div>

</div>

            <?php

        endwhile;

        echo '</div>';

        wp_reset_postdata();

    else :

        echo '<p>No jobs available at the moment.</p>';

    endif;

    return ob_get_clean();
});
