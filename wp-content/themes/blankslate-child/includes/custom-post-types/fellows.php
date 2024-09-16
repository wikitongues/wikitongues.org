<?php
add_action('init', 'create_post_type_fellows');

function create_post_type_fellows()
{
    register_taxonomy_for_object_type('category', 'fellows');
    register_taxonomy_for_object_type('post_tag', 'fellows');
    register_post_type('fellows',
        array(
        'labels' => array(
            'name' => __('Fellows', 'fellows'),
            'singular_name' => __('Fellow', 'fellows'),
            'add_new' => __('Add New', 'fellows'),
            'add_new_item' => __('Add New Fellow', 'fellows'),
            'edit' => __('Edit', 'fellows'),
            'edit_item' => __('Edit Fellow', 'fellows'),
            'new_item' => __('New Fellow', 'fellows'),
            'view' => __('View Fellow', 'fellows'),
            'view_item' => __('View Fellow', 'fellows'),
            'search_items' => __('Search Fellows', 'fellows'),
            'not_found' => __('No Fellows found', 'fellows'),
            'not_found_in_trash' => __('No Fellows found in Trash', 'fellows')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-money-alt',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail'
        ),
        'can_export' => true,
        // 'taxonomies' => array(
        //     'post_tag',
        //     'category'
        // )
    ));
}

// Add custom columns to the 'fellows' post type
function add_fellows_custom_columns($columns) {
	unset($columns['date']);
	$columns['fellow_year'] = __('Year', 'fellows');
	$columns['fellow_language'] = __('Language', 'fellows');

	return $columns;
}
add_filter('manage_fellows_posts_columns', 'add_fellows_custom_columns');

function fill_fellows_custom_columns($column, $post_id) {
	switch ($column) {
			case 'fellow_year':
					$fellow_year = get_field('fellow_year', $post_id);
					echo esc_html($fellow_year);
					break;

			case 'fellow_language':
					$fellow_language = get_field('fellow_language', $post_id);
					if ($fellow_language instanceof WP_Post) {
							// If the ACF field returns a post object, display the post title
							echo esc_html($fellow_language->post_title);
					} elseif (is_array($fellow_language)) {
							// If it's an array of post objects (multiple posts selected)
							$titles = wp_list_pluck($fellow_language, 'post_title'); // Get titles of all posts
							echo esc_html(implode(', ', $titles)); // Display them as a comma-separated list
					} else {
							echo __('No related post found', 'fellows'); // Handle cases where the field is empty or invalid
					}
					break;
	}
}
add_action('manage_fellows_posts_custom_column', 'fill_fellows_custom_columns', 10, 2);

function make_fellows_columns_sortable($columns) {
	$columns['fellow_year'] = 'fellow_year';
	$columns['fellow_language'] = 'fellow_language';
	return $columns;
}
add_filter('manage_edit-fellows_sortable_columns', 'make_fellows_columns_sortable');

// Modify the query to sort by custom fields
function fellows_custom_column_orderby($query) {
	if (!is_admin()) {
			return;
	}

	$orderby = $query->get('orderby');
	if ('fellow_year' == $orderby) {
			$query->set('meta_key', 'fellow_year');
			$query->set('orderby', 'meta_value_num');
	}
	if ('fellow_language' == $orderby) {
			$query->set('meta_key', 'fellow_language');
			$query->set('orderby', 'meta_value');
	}
}
add_action('pre_get_posts', 'fellows_custom_column_orderby');