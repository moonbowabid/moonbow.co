<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cpt_jobs_enqueue_assets() {

    wp_enqueue_style(
        'cpt-jobs-style',
        CPT_JOBS_URL . 'assets/css/career-jobs.css',
        [],
        '1.0.0'
    );
     wp_enqueue_script(
        'cpt-jobs-script',
        CPT_JOBS_URL . 'assets/js/career-jobs.js',
        ['jquery'],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'cpt_jobs_enqueue_assets');
