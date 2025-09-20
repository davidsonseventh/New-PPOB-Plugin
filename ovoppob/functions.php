<?php

// Memuat file CSS utama
function ovoppob_enqueue_styles() {
    wp_enqueue_style('ovoppob-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'ovoppob_enqueue_styles');

// Menambahkan dukungan dasar theme
function ovoppob_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'ovoppob_theme_setup');



/**
 * Mendaftarkan Custom Post Type untuk Event.
 */
function ovoppob_register_event_post_type() {
    $labels = array(
        'name'                  => _x( 'Events', 'Post type general name', 'ovoppob' ),
        'singular_name'         => _x( 'Event', 'Post type singular name', 'ovoppob' ),
        'menu_name'             => _x( 'Events', 'Admin Menu text', 'ovoppob' ),
        'name_admin_bar'        => _x( 'Event', 'Add New on Toolbar', 'ovoppob' ),
        'add_new'               => __( 'Add New', 'ovoppob' ),
        'add_new_item'          => __( 'Add New Event', 'ovoppob' ),
        'new_item'              => __( 'New Event', 'ovoppob' ),
        'edit_item'             => __( 'Edit Event', 'ovoppob' ),
        'view_item'             => __( 'View Event', 'ovoppob' ),
        'all_items'             => __( 'All Events', 'ovoppob' ),
        'search_items'          => __( 'Search Events', 'ovoppob' ),
        'parent_item_colon'     => __( 'Parent Events:', 'ovoppob' ),
        'not_found'             => __( 'No events found.', 'ovoppob' ),
        'not_found_in_trash'    => __( 'No events found in Trash.', 'ovoppob' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'event' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
    );

    register_post_type( 'event', $args );
}
add_action( 'init', 'ovoppob_register_event_post_type' );

?>
