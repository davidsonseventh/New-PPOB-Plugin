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

?>
