<?php
add_action('init', 'create_post_type_resources'); // indexing misc.
// revitalization project
// translation/interpretation
// language learning options
function create_post_type_resources()
{
    register_taxonomy_for_object_type('category', 'resources');
    register_taxonomy_for_object_type('post_tag', 'resources');
    register_post_type('resources',
        array(
        'labels' => array(
            'name' => __('Resources', 'resource'),
            'singular_name' => __('Resource', 'resource'),
            'add_new' => __('Add New', 'resource'),
            'add_new_item' => __('Add New Resource', 'resource'),
            'edit' => __('Edit', 'resource'),
            'edit_item' => __('Edit Resource', 'resource'),
            'new_item' => __('New Resource', 'resource'),
            'view' => __('View Resource', 'resource'),
            'view_item' => __('View Resource', 'resource'),
            'search_items' => __('Search Resources', 'resource'),
            'not_found' => __('No Resources found', 'resource'),
            'not_found_in_trash' => __('No Resources found in Trash', 'resource')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-format-quote',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt'
            // 'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}