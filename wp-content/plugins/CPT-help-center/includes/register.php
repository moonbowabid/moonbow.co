<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Help Center custom post type and taxonomy.
 */
function hc_register_help_center_post_type() {
    $labels = [
        'name'                  => _x( 'Help Center Items', 'Post type general name', 'help-center' ),
        'singular_name'         => _x( 'Help Center Item', 'Post type singular name', 'help-center' ),
        'menu_name'             => _x( 'Help Center', 'Admin Menu text', 'help-center' ),
        'name_admin_bar'        => _x( 'Help Center Item', 'Add New on Toolbar', 'help-center' ),
        'add_new'               => __( 'Add New', 'help-center' ),
        'add_new_item'          => __( 'Add New Help Center Item', 'help-center' ),
        'new_item'              => __( 'New Help Center Item', 'help-center' ),
        'edit_item'             => __( 'Edit Help Center Item', 'help-center' ),
        'view_item'             => __( 'View Help Center Item', 'help-center' ),
        'all_items'             => __( 'All Help Center Items', 'help-center' ),
        'search_items'          => __( 'Search Help Center', 'help-center' ),
        'parent_item_colon'     => __( 'Parent Help Center Items:', 'help-center' ),
        'not_found'             => __( 'No help center items found.', 'help-center' ),
        'not_found_in_trash'    => __( 'No help center items found in Trash.', 'help-center' ),
        'featured_image'        => _x( 'Help Center Featured Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'help-center' ),
        'set_featured_image'    => _x( 'Set featured image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'help-center' ),
        'remove_featured_image' => _x( 'Remove featured image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'help-center' ),
        'use_featured_image'    => _x( 'Use as featured image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'help-center' ),
        'archives'              => _x( 'Help Center item archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'help-center' ),
        'insert_into_item'      => _x( 'Insert into help center item', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'help-center' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this help center item', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase. Added in 4.4', 'help-center' ),
        'filter_items_list'     => _x( 'Filter help center items list', 'Screen reader text for the filter links heading on the post type listing screen. Added in 4.4', 'help-center' ),
        'items_list_navigation' => _x( 'Help center items list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Added in 4.4', 'help-center' ),
        'items_list'            => _x( 'Help center items list', 'Screen reader text for the items list heading on the post type listing screen. Added in 4.4', 'help-center' ),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => [
            'slug'       => 'help-center',
            'with_front' => false,
        ],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-editor-help',
        'supports'           => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields', 'revisions' ],
        'show_in_rest'       => true,
    ];

    register_post_type( 'help-center', $args );
}

function hc_register_help_center_taxonomy() {
    $labels = [
        'name'              => _x( 'Help Categories', 'taxonomy general name', 'help-center' ),
        'singular_name'     => _x( 'Help Category', 'taxonomy singular name', 'help-center' ),
        'search_items'      => __( 'Search Help Categories', 'help-center' ),
        'all_items'         => __( 'All Help Categories', 'help-center' ),
        'parent_item'       => __( 'Parent Help Category', 'help-center' ),
        'parent_item_colon' => __( 'Parent Help Category:', 'help-center' ),
        'edit_item'         => __( 'Edit Help Category', 'help-center' ),
        'update_item'       => __( 'Update Help Category', 'help-center' ),
        'add_new_item'      => __( 'Add New Help Category', 'help-center' ),
        'new_item_name'     => __( 'New Help Category Name', 'help-center' ),
        'menu_name'         => __( 'Help Categories', 'help-center' ),
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => [
            'slug'         => 'help-category',
            'with_front'   => false,
            'hierarchical' => true,
        ],
        'show_in_rest'      => true,
    ];

    register_taxonomy( 'help_category', [ 'help-center' ], $args );
}

add_action( 'init', 'hc_register_help_center_post_type' );
add_action( 'init', 'hc_register_help_center_taxonomy' );

function hc_flush_rewrite_rules_on_activation() {
    hc_register_help_center_post_type();
    hc_register_help_center_taxonomy();
    flush_rewrite_rules();
}

function hc_remove_help_center_rewrite_rules() {
    flush_rewrite_rules();
}
