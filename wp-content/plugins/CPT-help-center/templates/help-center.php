<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$terms = get_terms([
    'taxonomy'   => 'help_category',
    'hide_empty' => true,
    'orderby'    => 'term_id',
    'order'      => 'ASC',
]);

if ( empty( $terms ) || is_wp_error( $terms ) ) {
    return;
}
?> 

<div class="help-center-custom-post">
    <div class="help-center-custom-post-title">
        <h1 class="Archivo black">
            Help centre
        </h1>
    </div>

        <div class="help-center-search">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none"><script xmlns=""/>
        <path d="M38.9692 40.3071L26.4452 27.7831C25.4452 28.6351 24.2952 29.2944 22.9952 29.7611C21.6952 30.2278 20.3886 30.4611 19.0752 30.4611C15.8726 30.4611 13.1619 29.3524 10.9432 27.1351C8.72457 24.9178 7.61523 22.2078 7.61523 19.0051C7.61523 15.8024 8.72323 13.0911 10.9392 10.8711C13.1552 8.65111 15.8646 7.53978 19.0672 7.53711C22.2699 7.53445 24.9819 8.64378 27.2032 10.8651C29.4246 13.0864 30.5352 15.7978 30.5352 18.9991C30.5352 20.3884 30.2892 21.7331 29.7972 23.0331C29.3052 24.3331 28.6586 25.4451 27.8572 26.3691L40.3812 38.8911L38.9692 40.3071ZM19.0772 28.4591C21.7306 28.4591 23.9706 27.5458 25.7972 25.7191C27.6239 23.8924 28.5372 21.6518 28.5372 18.9971C28.5372 16.3424 27.6239 14.1024 25.7972 12.2771C23.9706 10.4518 21.7306 9.53845 19.0772 9.53711C16.4239 9.53578 14.1832 10.4491 12.3552 12.2771C10.5272 14.1051 9.6139 16.3451 9.61523 18.9971C9.61657 21.6491 10.5299 23.8891 12.3552 25.7171C14.1806 27.5451 16.4206 28.4584 19.0752 28.4571" fill="#621EFF"/>
        </svg>
           <input
                type="text"
                class="help-center-search__input"
                placeholder="Search"
                value="<?php echo isset($_GET['q']) ? esc_attr($_GET['q']) : ''; ?>"
            >

    </div>
    <div class="help-center-categorys-and-posts"> 
        <?php foreach ( $terms as $term ) : ?>
            <div class="help-center__category">

                <h3 class="help-center__category-title">
                    <?php echo esc_html( $term->name ); ?>
                </h3>

                <?php
                $query = new WP_Query([
                    'post_type'      => 'help-center',
                    'posts_per_page' => -1,
                    'orderby'        => 'date',
                    'order'          => 'ASC',
                    'tax_query'      => [[
                        'taxonomy' => 'help_category',
                        'field'    => 'term_id',
                        'terms'    => $term->term_id,
                    ]],
                ]);
                ?>

                <?php if ( $query->have_posts() ) : ?>
                    <ul class="help-center__category-list">
                        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                            <li class="help-center__category-item" data-content="<?php echo esc_attr( wp_strip_all_tags( get_the_content() ) ); ?>">
                                <a
                                    href="<?php echo esc_url( get_permalink() ); ?>"
                                    class="help-center__category-link"
                                >
                                    <?php the_title(); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>

            </div>
        <?php endforeach; ?>
    </div>
        <div class="help-center__no-results" style="display:none;">
            No results found
        </div>


</div>

