<?php
add_action('init', 'create_post_type_partners');
function create_post_type_partners()
{
    register_taxonomy_for_object_type('category', 'partners');
    register_taxonomy_for_object_type('post_tag', 'partners');
    register_post_type('partners',
        array(
        'labels' => array(
            'name' => __('Partners', 'partner'),
            'singular_name' => __('Partner', 'partner'),
            'add_new' => __('Add New', 'partner'),
            'add_new_item' => __('Add New Partner', 'partner'),
            'edit' => __('Edit', 'partner'),
            'edit_item' => __('Edit Partner', 'partner'),
            'new_item' => __('New Partner', 'partner'),
            'view' => __('View Partner', 'partner'),
            'view_item' => __('View Partner', 'partner'),
            'search_items' => __('Search Partners', 'partner'),
            'not_found' => __('No Partners found', 'partner'),
            'not_found_in_trash' => __('No Partners found in Trash', 'partner')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-heart',
        'has_archive' => true,
        'supports' => array(
            'title'
        ),
        'can_export' => true,
        'taxonomies' => array(
            // 'post_tag',
            // 'category'
        ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}

add_filter('manage_partners_posts_columns', 'add_partners_custom_columns');
function add_partners_custom_columns($columns) {
	unset($columns['date']);
	$columns['partner_type'] = __('Type', 'partners');
    var_dump($columns);
	return $columns;
}

add_action('manage_partners_posts_custom_column', 'fill_partners_custom_columns', 10, 2);
function fill_partners_custom_columns($column, $post_id) {
    $partner_type = get_field('partner_type', $post_id);
    echo esc_html($partner_type);
}

add_filter('manage_edit-partners_sortable_columns', 'make_partners_columns_sortable');
function make_partners_columns_sortable($columns) {
	$columns['partner_type'] = 'partner_type';
	return $columns;
}