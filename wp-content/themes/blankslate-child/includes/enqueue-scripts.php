<?php
// Enqueue stylesheets
add_action('wp_enqueue_scripts', 'wt_enqueue_styles');
function wt_enqueue_styles() {
    $parenthandle = 'blankslate-style';
    $theme = wp_get_theme();

    // If parent theme is not available, use theme stylesheet
    $parent_version = $theme->parent() ? $theme->parent()->get('Version') : $theme->get('Version');

    wp_enqueue_style($parenthandle, get_template_directory_uri() . '/style.css', array(), $parent_version);
    wp_enqueue_style('child-style', get_stylesheet_uri(), array($parenthandle), $theme->get('Version'));
}

// Enqueue custom JavaScript
add_action('wp_enqueue_scripts', 'wt_enqueue_js');
function wt_enqueue_js() {
    wp_register_script('wt_js', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery'), true);
    wp_enqueue_script('wt_js');
}