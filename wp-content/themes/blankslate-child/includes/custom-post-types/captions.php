<?php
add_action('init', 'create_post_type_captions');
function create_post_type_captions()
{
  register_taxonomy_for_object_type('category', 'captions');
	register_taxonomy_for_object_type('post_tag', 'captions');
	register_post_type('captions',
		array(
		'labels' => array(
			'name' => __('Captions', 'caption'),
			'singular_name' => __('Caption', 'caption'),
			'add_new' => __('Add New', 'caption'),
			'add_new_item' => __('Add New Caption', 'caption'),
			'edit' => __('Edit', 'caption'),
			'edit_item' => __('Edit Caption', 'caption'),
			'new_item' => __('New Caption', 'caption'),
			'view' => __('View Caption', 'caption'),
			'view_item' => __('View Caption', 'caption'),
			'search_items' => __('Search Captions', 'caption'),
			'not_found' => __('No Captions found', 'caption'),
			'not_found_in_trash' => __('No Captions found in Trash', 'caption')
		),
		'public' => true,
		'supports' => ['title', 'custom-fields'],
		'has_archive' => true,
		'show_in_rest' => true,
		'rest_controller_class' => 'WT_REST_Posts_Controller'
	));
}