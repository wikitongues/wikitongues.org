<?php
// Register Custom Post Type for FAQs
add_action('init', 'create_events_cpt');
function create_events_cpt() {
    $labels = array(
        'name' => __('Events', 'textdomain'),
        'singular_name' => __('Event', 'textdomain'),
        'menu_name' => __('Events', 'textdomain'),
        'name_admin_bar' => __('Event', 'textdomain'),
        'add_new' => __('Add New', 'textdomain'),
        'add_new_item' => __('Add New Event', 'textdomain'),
        'new_item' => __('New Event', 'textdomain'),
        'edit_item' => __('Edit Event', 'textdomain'),
        'view_item' => __('View Event', 'textdomain'),
        'all_items' => __('All Events', 'textdomain'),
        'search_items' => __('Search Events', 'textdomain'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'rewrite' => array('slug' => 'events'),
        'supports' => array('title', 'editor', 'revisions'),
        'menu_icon' => 'dashicons-calendar-alt',
        'show_in_rest' => true,
    );

    register_post_type('events', $args);
}
