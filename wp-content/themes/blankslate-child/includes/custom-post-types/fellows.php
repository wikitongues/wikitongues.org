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

// ====================
// Manage Custom Columns
// ====================
// Add custom columns to the 'fellows' post type
add_filter('manage_fellows_posts_columns', 'add_fellows_custom_columns');
function add_fellows_custom_columns($columns) {
	unset($columns['date']);
	$columns['fellow_year'] = __('Year', 'fellows');
	$columns['fellow_language'] = __('Language', 'fellows');

	return $columns;
}

add_action('manage_fellows_posts_custom_column', 'fill_fellows_custom_columns', 10, 2);
function fill_fellows_custom_columns($column, $post_id) {
	switch ($column) {
		case 'fellow_year':
			$fellow_year = get_field('fellow_year', $post_id);
			echo esc_html($fellow_year);
			break;

		case 'fellow_language':
			$fellow_language = get_field('fellow_language', $post_id);

			// If $fellow_language is an array and the first element is a WP_Post object, use wp_list_pluck
			if (is_array($fellow_language) && isset($fellow_language[0]) && $fellow_language[0] instanceof WP_Post) {
				$titles = wp_list_pluck($fellow_language, 'post_title');
				echo esc_html(implode(', ', $titles));
			} elseif ($fellow_language instanceof WP_Post) {
				// Single WP_Post object
				echo esc_html($fellow_language->post_title);
			} elseif (is_array($fellow_language)) {
				// Handle an array of IDs instead of objects
				$titles = [];
				foreach ($fellow_language as $language_id) {
					$language_post = get_post($language_id);
					if ($language_post) {
						$titles[] = $language_post->post_title;
					}
				}
				echo esc_html(implode(', ', $titles));
			} else {
				// No valid post object or array of objects/IDs
				echo __('No related post found', 'fellows');
			}
			break;
	}
}

add_filter('manage_edit-fellows_sortable_columns', 'make_fellows_columns_sortable');
function make_fellows_columns_sortable($columns) {
	$columns['fellow_year'] = 'fellow_year';
	$columns['fellow_language'] = 'fellow_language';
	return $columns;
}

// Modify the query to sort by custom fields
add_action('pre_get_posts', 'fellows_custom_column_orderby');
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