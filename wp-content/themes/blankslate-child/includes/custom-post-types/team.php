<?php
add_action('init', 'create_post_type_team');
function create_post_type_team()
{
    register_taxonomy_for_object_type('category', 'team');
    register_taxonomy_for_object_type('post_tag', 'team');
    register_post_type('team',
        array(
        'labels' => array(
            'name' => __('Team', 'team'),
            'singular_name' => __('Team', 'team'),
            'add_new' => __('Add New', 'team'),
            'add_new_item' => __('Add New team', 'team'),
            'edit' => __('Edit', 'team'),
            'edit_item' => __('Edit Team', 'team'),
            'new_item' => __('New Team', 'team'),
            'view' => __('View Team', 'team'),
            'view_item' => __('View Team', 'team'),
            'search_items' => __('Search Team', 'team'),
            'not_found' => __('No Team found', 'team'),
            'not_found_in_trash' => __('No Team found in Trash', 'team')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-megaphone',
        'has_archive' => true,
        'supports' => array(
            'title',
            'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true
    ));
}