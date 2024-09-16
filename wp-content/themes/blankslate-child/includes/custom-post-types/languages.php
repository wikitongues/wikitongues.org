<?php
add_action('init', 'create_post_type_languages');
function create_post_type_languages()
{
    register_taxonomy_for_object_type('category', 'languages');
    register_taxonomy_for_object_type('post_tag', 'languages');
    register_post_type('languages',
        array(
        'labels' => array(
            'name' => __('Languages', 'language'),
            'singular_name' => __('Language', 'language'),
            'add_new' => __('Add New', 'language'),
            'add_new_item' => __('Add New Language', 'language'),
            'edit' => __('Edit', 'language'),
            'edit_item' => __('Edit Language', 'language'),
            'new_item' => __('New Language', 'language'),
            'view' => __('View Language', 'language'),
            'view_item' => __('View Language', 'language'),
            'search_items' => __('Search Languages', 'language'),
            'not_found' => __('No Languages found', 'language'),
            'not_found_in_trash' => __('No language Items found in Trash', 'language')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-translation',
        'has_archive' => true,
        'supports' => array(
            'title',
            'thumbnail',
            'excerpt'
        ),
        'can_export' => true,
        // 'taxonomies' => array(
        //     'post_tag',
        //     'category'
        // ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}


// Add custom columns to the 'fellows' post type
function add_languages_custom_columns($columns) {
	unset($columns['date']);
	$columns['standard_name'] = __('Name', 'languages');
	$columns['nations_of_origin'] = __('Nations', 'fellows');
    $columns['speakers_recorded'] = __('Videos', 'fellows');

	return $columns;
}
add_filter('manage_languages_posts_columns', 'add_languages_custom_columns');

function fill_languages_custom_columns($column, $post_id) {
	switch ($column) {
        case 'standard_name':
            $standard_name = get_field('standard_name', $post_id);
            echo esc_html($standard_name);
            break;

        case 'nations_of_origin':
            $nations_of_origin = get_field('nations_of_origin', $post_id);
            echo esc_html($nations_of_origin);
            break;

        case 'speakers_recorded':
            $speakers_recorded = get_field('speakers_recorded', $post_id);
            if ($speakers_recorded instanceof WP_Post) {
                // If the ACF field returns a post object, display the post title
                echo esc_html($speakers_recorded->post_title);
            } elseif (is_array($speakers_recorded)) {
                // If it's an array of post objects (multiple posts selected)
                $titles = wp_list_pluck($speakers_recorded, 'post_title'); // Get titles of all posts
                echo esc_html(implode(', ', $titles)); // Display them as a comma-separated list
            } else {
                echo __('No related post found', 'languages'); // Handle cases where the field is empty or invalid
            }
            break;
	}
}
add_action('manage_languages_posts_custom_column', 'fill_languages_custom_columns', 10, 2);

function make_languages_columns_sortable($columns) {
	$columns['standard_name'] = 'standard_name';
    $columns['nations_of_origin'] = 'nations_of_origin';
    $columns['speakers_recorded'] = 'speakers_recorded';
	return $columns;
}
add_filter('manage_edit-languages_sortable_columns', 'make_languages_columns_sortable');

// // Modify the query to sort by custom fields
function languages_custom_column_orderby($query) {
	if (!is_admin()) {
			return;
	}

	$orderby = $query->get('orderby');
	if ('standard_name' == $orderby) {
			$query->set('meta_key', 'standard_name');
			$query->set('orderby', 'meta_value');
	}
	if ('nations_of_origin' == $orderby) {
			$query->set('meta_key', 'nations_of_origin');
			$query->set('orderby', 'meta_value');
	}
    if ('speakers_recorded' == $orderby) {
        $query->set('meta_key', 'speakers_recorded');
        $query->set('orderby', 'meta_value');
}
}
add_action('pre_get_posts', 'languages_custom_column_orderby');